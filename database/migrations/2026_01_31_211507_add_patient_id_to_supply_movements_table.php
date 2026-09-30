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
        Schema::table('supply_movements', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->after('supply_id')->constrained()->onDelete('set null');
            $table->text('description')->nullable()->after('reason'); // To record the delivery proof details
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_movements', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn(['patient_id', 'description']);
        });
    }
};
