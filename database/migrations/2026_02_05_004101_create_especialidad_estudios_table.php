<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('especialidad_estudios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('especialidad_id')->constrained('medical_specialties')->onDelete('cascade');
            $table->string('estudio');
            $table->decimal('costo', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('especialidad_estudios');
    }
};
