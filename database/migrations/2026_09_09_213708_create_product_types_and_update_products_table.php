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
        // 1. Buat tabel product_types
        if (!\Illuminate\Support\Facades\Schema::hasTable('product_types')) {
            \Illuminate\Support\Facades\Schema::create('product_types', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('code', 50)->unique();
                $table->string('color', 30)->default('primary');
                $table->string('icon', 100)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Isi default types agar data eksisting sinkron
        $now = now();
        $defaults = [
            [
                'name' => 'Isi Ulang',
                'code' => 'REFILL',
                'color' => 'info',
                'icon' => 'heroicon-o-arrow-path',
                'description' => 'Isi ulang galon - memerlukan galon kosong',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Unit Baru (Galon + Isi)',
                'code' => 'NEW_UNIT',
                'color' => 'success',
                'icon' => 'heroicon-o-plus-circle',
                'description' => 'Galon baru + isi air - menambah saldo galon',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Barang Konsumsi',
                'code' => 'CONSUMABLE',
                'color' => 'warning',
                'icon' => 'heroicon-o-shopping-bag',
                'description' => 'Produk konsumsi lainnya (tissue, gelas, dll)',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($defaults as $item) {
            \Illuminate\Support\Facades\DB::table('product_types')->updateOrInsert(
                ['code' => $item['code']],
                $item
            );
        }

        // 3. Ubah kolom product_type di tabel products dari ENUM menjadi VARCHAR(50)
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `products` MODIFY `product_type` VARCHAR(50) NOT NULL DEFAULT 'REFILL'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `products` MODIFY `product_type` ENUM('REFILL', 'NEW_UNIT', 'CONSUMABLE') NOT NULL");
        } catch (\Throwable $e) {
            // Abaikan jika ada data selain enum bawaan
        }

        \Illuminate\Support\Facades\Schema::dropIfExists('product_types');
    }
};
