<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\CashMovement;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        CashMovement::whereHas('appointment', function ($q) {
            $q->where('payment_status', 'unpaid');
        })->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
