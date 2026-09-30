<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * MySQL crea índices en las claves foráneas automáticamente; PostgreSQL no.
 * Esta migración agrega esos índices solo cuando la base es PostgreSQL.
 */
return new class extends Migration
{
    private array $indexes = [
        'appointments' => ['patient_id', 'doctor_id'],
        'medical_histories' => ['patient_id', 'doctor_id'],
        'supply_movements' => ['supply_id', 'patient_id', 'user_id'],
        'cash_movements' => ['appointment_id', 'doctor_id', 'user_id'],
        'doctor_specialty' => ['user_id', 'medical_specialty_id'],
        'especialidad_estudios' => ['especialidad_id'],
    ];

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->indexes as $table => $columns) {
            Schema::table($table, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->index($column);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->indexes as $table => $columns) {
            Schema::table($table, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->dropIndex([$column]);
                }
            });
        }
    }
};
