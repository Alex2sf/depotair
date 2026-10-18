<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('cash_transactions') && !Schema::hasColumn('cash_transactions', 'on_behalf_of')) {
            Schema::table('cash_transactions', function (Blueprint $table) {
                $table->foreignId('on_behalf_of')
                    ->nullable()
                    ->after('recorded_by')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cash_transactions') && Schema::hasColumn('cash_transactions', 'on_behalf_of')) {
            Schema::table('cash_transactions', function (Blueprint $table) {
                $table->dropForeign(['on_behalf_of']);
                $table->dropColumn('on_behalf_of');
            });
        }
    }
};
