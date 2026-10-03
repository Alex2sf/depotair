<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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
                    WHEN type = 'DEPOSIT' OR LOWER(description) LIKE '%setor%' THEN 'Setor Kas'
                    ELSE 'Pengeluaran Kas'
                END as category,
                'TUNAI' as payment_method,
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
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke definisi awal jika di-rollback
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
                CASE WHEN type = 'DEPOSIT' THEN 'PEMASUKAN' ELSE 'PENGELUARAN' END as type,
                CASE WHEN type = 'DEPOSIT' THEN 'Kas Masuk' ELSE 'Pengeluaran Kas' END as category,
                'TUNAI' as payment_method,
                CASE WHEN type = 'DEPOSIT' THEN amount ELSE 0 END as amount_in,
                CASE WHEN type = 'EXPENSE' THEN amount ELSE 0 END as amount_out,
                description as description,
                proof_image as proof_image
            FROM cash_transactions
            WHERE order_id IS NULL
        ");
    }
};
