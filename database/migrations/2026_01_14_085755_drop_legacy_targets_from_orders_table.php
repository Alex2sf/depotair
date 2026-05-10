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
            $table->dropColumn(['prepared_target_at', 'ready_target_at', 'delivery_target_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('prepared_target_at')->nullable();
            $table->timestamp('ready_target_at')->nullable();
            $table->timestamp('delivery_target_at')->nullable();
        });
    }
};
