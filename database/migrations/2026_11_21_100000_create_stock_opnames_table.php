<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Riwayat opname: stok sistem saat dihitung disimpan berdampingan dengan hitungan fisik,
// supaya selisih tiap opname tetap bisa dilihat walau stok sistem berubah sesudahnya.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('system_quantity');
            $table->integer('real_quantity');
            $table->integer('difference');
            $table->string('notes', 500)->nullable();
            $table->timestamp('counted_at');
            $table->timestamps();

            $table->index(['product_id', 'counted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};
