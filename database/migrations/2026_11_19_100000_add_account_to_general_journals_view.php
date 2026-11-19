<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Menambah kolom `account` (LACI / KAS_BESAR / QRIS / TRANSFER / CORPORATE) supaya jurnal bisa dipisah
// per rekening seperti mutasi BCA, dan pengadaan stok dari Kas Besar tidak lagi tercatat sebagai TUNAI.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW general_journals AS
            SELECT
                CONCAT('ORD-', id) as id,
                'ORDER' as source_type,
                id as source_id,
                order_number as ref_number,
                created_at as transaction_date,
                'PEMASUKAN' as type,
                'Penjualan' as category,
                payment_type as payment_method,
                CASE payment_type
                    WHEN 'TUNAI' THEN 'LACI'
                    WHEN 'QRIS' THEN 'QRIS'
                    WHEN 'TRANSFER' THEN 'TRANSFER'
                    ELSE 'CORPORATE'
                END as account,
                total_amount as amount_in,
                0 as amount_out,
                CONCAT('Penjualan Nota #', order_number) as description,
                NULL as proof_image
            FROM orders
            WHERE status = 'COMPLETE'

            UNION ALL

            SELECT
                CONCAT('BLJ-', id) as id,
                'PURCHASE' as source_type,
                id as source_id,
                CONCAT('BLJ-', LPAD(id, 5, '0')) as ref_number,
                created_at as transaction_date,
                'PENGELUARAN' as type,
                CASE WHEN category = 'STOCK' THEN 'Belanja Stok' ELSE 'Operasional Toko' END as category,
                'TUNAI' as payment_method,
                'LACI' as account,
                0 as amount_in,
                amount as amount_out,
                description as description,
                proof_image as proof_image
            FROM cashier_purchases

            UNION ALL

            SELECT
                CONCAT('TRX-', id) as id,
                'CASH_TRX' as source_type,
                id as source_id,
                CONCAT('TRX-', LPAD(id, 5, '0')) as ref_number,
                created_at as transaction_date,
                CASE
                    WHEN LOWER(description) LIKE '%modal%' THEN 'PEMASUKAN'
                    ELSE 'PENGELUARAN'
                END as type,
                CASE
                    WHEN LOWER(description) LIKE '%modal%' THEN 'Modal Kas'
                    WHEN cash_type = 'MAIN' AND description LIKE 'Pengadaan Stok%' THEN 'Belanja Stok'
                    WHEN type = 'DEPOSIT' OR LOWER(description) LIKE '%setor%' THEN 'Setor Kas'
                    ELSE 'Pengeluaran Kas'
                END as category,
                CASE WHEN cash_type = 'MAIN' THEN 'KAS BESAR' ELSE 'TUNAI' END as payment_method,
                CASE WHEN cash_type = 'MAIN' THEN 'KAS_BESAR' ELSE 'LACI' END as account,
                CASE
                    WHEN LOWER(description) LIKE '%modal%' THEN amount
                    ELSE 0
                END as amount_in,
                CASE
                    WHEN LOWER(description) LIKE '%modal%' THEN 0
                    ELSE amount
                END as amount_out,
                description as description,
                proof_image as proof_image
            FROM cash_transactions
            WHERE order_id IS NULL
              AND description NOT LIKE 'Belanja Stok:%'
              AND description NOT LIKE 'Operasional:%'
        ");
    }

    public function down(): void
    {
        // Kembali ke definisi 2026_10_18_131500 (tanpa kolom account)
        (require __DIR__ . '/2026_10_18_131500_fix_general_journals_view_exclude_duplicate_purchases.php')->up();
    }
};
