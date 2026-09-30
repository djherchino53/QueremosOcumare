<?php

/*
 * Copia los datos de la base MySQL local (conexión definida en .env) a una
 * base PostgreSQL (por ejemplo Supabase).
 *
 * Uso:
 *   TARGET_DB_URL="postgresql://usuario:clave@host:5432/postgres?sslmode=require" \
 *     php database/tools/mysql_to_pgsql.php [--dry-run]
 *
 * 1. Crea el esquema en el destino ejecutando las migraciones de Laravel.
 * 2. Vacía las tablas de negocio del destino y copia todas las filas con sus IDs.
 * 3. Ajusta las secuencias de los IDs y compara el conteo de filas.
 *
 * No copia sesiones, caché, colas ni tokens: son datos temporales.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$dryRun = in_array('--dry-run', $argv, true);
$targetUrl = getenv('TARGET_DB_URL');

if (! $targetUrl) {
    fwrite(STDERR, "Falta la variable TARGET_DB_URL.\n");
    exit(1);
}

// En orden de dependencias: primero las tablas referenciadas por claves foráneas.
$tables = [
    'users',
    'patients',
    'medical_specialties',
    'supplies',
    'appointments',
    'medical_histories',
    'supply_movements',
    'cash_movements',
    'doctor_profiles',
    'doctor_specialty',
    'especialidad_estudios',
];

config(['database.connections.target' => array_merge(
    config('database.connections.pgsql'),
    ['url' => $targetUrl]
)]);

$source = DB::connection(config('database.default'));
$target = DB::connection('target');

if ($source->getDriverName() !== 'mysql') {
    fwrite(STDERR, "La conexión de origen debe ser MySQL (DB_CONNECTION=mysql en .env).\n");
    exit(1);
}

echo "Origen:  {$source->getDatabaseName()} (mysql)\n";
echo "Destino: " . $target->selectOne('select current_database() as db')->db . " (pgsql)\n\n";

if ($dryRun) {
    foreach ($tables as $table) {
        printf("  %-22s %5d filas\n", $table, $source->table($table)->count());
    }
    echo "\n--dry-run: no se modificó nada.\n";
    exit(0);
}

echo "Ejecutando migraciones en el destino...\n";
Artisan::call('migrate', ['--database' => 'target', '--force' => true]);
echo Artisan::output();

$target->transaction(function () use ($source, $target, $tables) {
    $target->statement('TRUNCATE ' . implode(', ', $tables) . ' RESTART IDENTITY CASCADE');

    foreach ($tables as $table) {
        $rows = $source->table($table)->orderBy('id')->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            $target->table($table)->insert($chunk);
        }

        $target->statement(
            "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM {$table}"
        );
    }
});

echo "\nVerificación:\n";
$ok = true;
foreach ($tables as $table) {
    $from = $source->table($table)->count();
    $to = $target->table($table)->count();
    $ok = $ok && $from === $to;
    printf("  %-22s mysql=%-5d pgsql=%-5d %s\n", $table, $from, $to, $from === $to ? 'OK' : 'DIFERENTE');
}

echo $ok ? "\nMigración completa.\n" : "\nHay diferencias: revisa la salida.\n";
exit($ok ? 0 : 1);
