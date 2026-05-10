<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('sku', 100)->unique();
            $table->string('image_url');
            $table->enum('product_type', ['REFILL', 'NEW_UNIT', 'CONSUMABLE']); // Adjust as needed
            $table->string('unit', 50); // e.g., 'pcs', 'gallon', 'unit'
            $table->integer('price')->unsigned(); // Price in smallest currency unit (cents/rupiah)
            $table->integer('cogs')->unsigned(); // Cost of goods sold
            $table->text('description')->nullable();
            $table->boolean('is_enabled');
            $table->timestamps();

            $table->index('sku');
            $table->index('product_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
