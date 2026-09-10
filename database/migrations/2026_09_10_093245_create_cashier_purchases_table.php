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
        if (!Schema::hasTable('cashier_purchases')) {
            Schema::create('cashier_purchases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('category', ['STOCK', 'OPERATIONAL'])->default('STOCK');
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->integer('quantity')->default(0);
                $table->bigInteger('amount'); // Total biaya yang diambil dari laci kasir
                $table->string('description', 255);
                $table->string('proof_image', 255)->nullable(); // Foto nota/struk/barang
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index('category');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_purchases');
    }
};
