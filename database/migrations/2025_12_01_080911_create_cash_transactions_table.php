<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
// database/migrations/xxxx_xx_xx_xxxxxx_create_cash_transactions_table.php
    public function up()
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['EXPENSE', 'DEPOSIT']);
            $table->bigInteger('amount')->unsigned();
            $table->text('description');
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['type', 'created_at']);
        });
    }
    public function down()
    {
        Schema::dropIfExists('cash_transactions');
    }
};
