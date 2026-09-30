<?php

/*
 * Genera un archivo .sql para PostgreSQL (Supabase) con el esquema completo
 * y todos los datos de la base MySQL local definida en .env.
 *
 * Uso:
 *   php database/tools/export_pgsql_sql.php [ruta_de_salida]
 *
 * Salida por defecto: database/supabase/queremos_ocumare.sql
 * (esa carpeta está en .gitignore porque el archivo contiene datos de pacientes).
 *
 * El esquema sale de las migraciones de Laravel con la gramática de PostgreSQL,
 * así que coincide con lo que haría `php artisan migrate`. También se registran
 * las migraciones como ejecutadas, para que un `migrate` posterior no intente
 * crear las tablas otra vez.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$output = $argv[1] ?? __DIR__ . '/../supabase/queremos_ocumare.sql';

// En orden de dependencias: primero las tablas referenciadas por claves foráneas.
$dataTables = [
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

// Migraciones que solo modifican datos y no tienen sentido en una base nueva.
$dataOnlyMigrations = ['2026_02_12_000001_cleanup_unpaid_cash_movements'];

$source = DB::connection(config('database.default'));

if ($source->getDriverName() !== 'mysql') {
    fwrite(STDERR, "La conexión de origen debe ser MySQL (DB_CONNECTION=mysql en .env).\n");
    exit(1);
}

// La app trabaja en UTC; sin esto MySQL devuelve las fechas en la hora local del servidor.
$source->statement("SET time_zone = '+00:00'");

$literal = fn ($value) => $value === null ? 'NULL' : "'" . str_replace("'", "''", (string) $value) . "'";

$sql = [];
$sql[] = '-- Base de datos Queremos Ocumare para PostgreSQL (Supabase)';
$sql[] = '-- Generado el ' . date('Y-m-d H:i:s') . ' desde MySQL "' . $source->getDatabaseName() . '"';
$sql[] = '-- Contiene datos personales y médicos: no lo subas a un repositorio público.';
$sql[] = '';
$sql[] = 'BEGIN;';
$sql[] = '';

// Esquema desde las migraciones, sin conectarse a PostgreSQL.
DB::setDefaultConnection('pgsql');
$pgsql = DB::connection('pgsql');

$sql[] = '-- Esquema';
$sql[] = 'create table "migrations" ("id" serial not null primary key, "migration" varchar(255) not null, "batch" integer not null);';

$migrationNames = [];
foreach (glob(__DIR__ . '/../migrations/*.php') as $file) {
    $name = basename($file, '.php');
    $migrationNames[] = $name;

    if (in_array($name, $dataOnlyMigrations, true)) {
        continue;
    }

    $migration = require $file;
    foreach ($pgsql->pretend(fn () => $migration->up()) as $query) {
        $sql[] = $query['query'] . ';';
    }
}

DB::setDefaultConnection($source->getName());

// Registro de migraciones ejecutadas.
$sql[] = '';
$sql[] = '-- Migraciones ejecutadas';
foreach ($migrationNames as $name) {
    $sql[] = 'insert into "migrations" ("migration", "batch") values (' . $literal($name) . ', 1);';
}

// Datos.
$rowCounts = [];
foreach ($dataTables as $table) {
    $rows = $source->table($table)->orderBy('id')->get();
    $rowCounts[$table] = $rows->count();

    $sql[] = '';
    $sql[] = "-- Datos: {$table} ({$rows->count()} filas)";

    foreach ($rows as $row) {
        $row = (array) $row;
        $columns = implode(', ', array_map(fn ($c) => "\"{$c}\"", array_keys($row)));
        $values = implode(', ', array_map($literal, array_values($row)));
        $sql[] = "insert into \"{$table}\" ({$columns}) values ({$values});";
    }

    $sql[] = "select setval(pg_get_serial_sequence('\"{$table}\"', 'id'), coalesce(max(\"id\"), 1), max(\"id\") is not null) from \"{$table}\";";
}

// Supabase expone las tablas de "public" por su API REST. Activar RLS sin políticas
// bloquea ese acceso; Laravel entra con el usuario postgres, que no está sujeto a RLS.
$sql[] = '';
$sql[] = '-- Bloquear el acceso por la API REST de Supabase';
$allTables = array_merge(
    ['migrations', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'],
    $dataTables
);
foreach ($allTables as $table) {
    $sql[] = "alter table \"{$table}\" enable row level security;";
}

$sql[] = '';
$sql[] = 'COMMIT;';
$sql[] = '';

if (! is_dir(dirname($output))) {
    mkdir(dirname($output), 0777, true);
}
file_put_contents($output, implode("\n", $sql));

echo "Archivo generado: " . realpath($output) . "\n";
foreach ($rowCounts as $table => $count) {
    printf("  %-22s %5d filas\n", $table, $count);
}
