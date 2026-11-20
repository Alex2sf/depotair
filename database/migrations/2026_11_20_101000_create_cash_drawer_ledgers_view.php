<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Buku Kas Laci: hanya transaksi yang menggerakkan uang fisik di laci (cash_type = CASHIER),
// dengan saldo berjalan dihitung dari seluruh riwayat (filter tanggal di luar view tidak merusak saldonya).
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW cash_drawer_ledgers AS
            SELECT
                t.id,
                t.created_at AS transaction_date,
                t.description,
                t.recorded_by,
                t.order_id,
                t.proof_image,
                t.category,
                t.amount_in,
                t.amount_out,
                SUM(t.amount_in - t.amount_out) OVER (ORDER BY t.created_at, t.id) AS running_balance
            FROM (
                SELECT
                    id, created_at, description, recorded_by, order_id, proof_image,
                    CASE
                        WHEN description LIKE 'Setor ke kas besar%' THEN 'Setor Kas Besar'
                        WHEN description LIKE 'Penjualan TUNAI%' THEN 'Penjualan'
                        WHEN description LIKE 'Void/Batal%' THEN 'Batal'
                        WHEN description LIKE 'Belanja Stok:%' THEN 'Belanja Stok'
                        WHEN description LIKE 'Operasional:%' THEN 'Operasional'
                        WHEN LOWER(description) LIKE '%modal%' THEN 'Modal'
                        WHEN type = 'DEPOSIT' THEN 'Uang Masuk Lain'
                        ELSE 'Uang Keluar Lain'
                    END AS category,
                    CASE
                        WHEN type = 'DEPOSIT' AND description NOT LIKE 'Setor ke kas besar%' THEN amount
                        ELSE 0
                    END AS amount_in,
                    CASE
                        WHEN type = 'EXPENSE' OR description LIKE 'Setor ke kas besar%' THEN amount
                        ELSE 0
                    END AS amount_out
                FROM cash_transactions
                WHERE cash_type = 'CASHIER'
            ) t
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS cash_drawer_ledgers');
    }
};
