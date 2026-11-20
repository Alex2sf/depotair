<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            // Snapshot saldo laci di sistem (cash_balances CASHIER) saat shift ditutup, untuk dibandingkan dengan expected_cash
            $table->bigInteger('system_balance_at_close')->nullable()->after('expected_cash');
        });
    }

    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropColumn('system_balance_at_close');
        });
    }
};
