<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('product_name', 255); // Snapshot of product name at time of sale
            $table->integer('quantity')->unsigned();
            $table->integer('price_at_sale')->unsigned(); // Price at the time of sale
            $table->integer('cogs_at_sale')->unsigned(); // COGS at the time of sale
            $table->integer('subtotal')->unsigned(); // quantity * price_at_sale

            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_product');
    }
};
