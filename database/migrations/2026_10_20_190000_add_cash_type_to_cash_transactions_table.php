<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('cash_transactions') && !Schema::hasColumn('cash_transactions', 'cash_type')) {
            Schema::table('cash_transactions', function (Blueprint $table) {
                $table->string('cash_type', 20)->default('CASHIER')->after('type');
            });
        }

        // Tandai transaksi pengadaan stok yang sudah ada sebagai milik Kas Besar (MAIN)
        DB::table('cash_transactions')
            ->where('description', 'like', 'Pengadaan Stok%')
            ->update(['cash_type' => 'MAIN']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cash_transactions') && Schema::hasColumn('cash_transactions', 'cash_type')) {
            Schema::table('cash_transactions', function (Blueprint $table) {
                $table->dropColumn('cash_type');
            });
        }
    }
};
