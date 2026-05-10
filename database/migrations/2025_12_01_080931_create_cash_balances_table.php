<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   // database/migrations/xxxx_xx_xx_xxxxxx_create_cash_balances_table.php
    public function up()
    {
        Schema::create('cash_balances', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['CASHIER', 'MAIN'])->unique();
            $table->bigInteger('balance')->default(0);
            $table->timestamp('last_transaction_at')->nullable();
            $table->timestamps();
        });

        // Isi data awal
        DB::table('cash_balances')->insert([
            ['type' => 'CASHIER', 'balance' => 0],
            ['type' => 'MAIN',    'balance' => 0],
        ]);
    }
    public function down()
    {
        Schema::dropIfExists('cash_balances');
    }
};
