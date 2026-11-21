<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Produk yang dibuat lewat Filament sebelum ada pembuatan otomatis tidak punya baris inventori.
// Dibuatkan dengan stok 0 (tanpa riwayat stok, karena memang tidak ada barang yang tercatat masuk).
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            INSERT INTO inventories (product_id, quantity, real_quantity, low_stock_threshold, created_at, updated_at)
            SELECT p.id, 0, 0, 10, NOW(), NOW()
            FROM products p
            LEFT JOIN inventories i ON i.product_id = p.id
            WHERE i.id IS NULL
        ");
    }

    public function down(): void
    {
        // Tidak ada yang dibatalkan: baris inventori stok 0 aman dibiarkan.
    }
};
