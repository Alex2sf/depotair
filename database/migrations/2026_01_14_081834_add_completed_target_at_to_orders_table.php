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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'prepared_target_at')) {
                $table->timestamp('prepared_target_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'ready_target_at')) {
                $table->timestamp('ready_target_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'delivery_target_at')) {
                $table->timestamp('delivery_target_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'completed_target_at')) {
                $table->timestamp('completed_target_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('completed_target_at');
        });
    }
};
