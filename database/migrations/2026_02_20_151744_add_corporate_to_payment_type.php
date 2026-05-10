<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter enum to add CORPORATE
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_type ENUM('TUNAI','QRIS','TRANSFER','CORPORATE') NOT NULL");

        // Recreate view to include CORPORATE
        DB::statement("
            CREATE OR REPLACE VIEW daily_payment_reports AS
            SELECT 
                MIN(id) as id,
                DATE(created_at) as date,
                SUM(CASE WHEN payment_type = 'TUNAI' THEN total_amount ELSE 0 END) as tunai_total,
                SUM(CASE WHEN payment_type = 'QRIS' THEN total_amount ELSE 0 END) as qris_total,
                SUM(CASE WHEN payment_type = 'TRANSFER' THEN total_amount ELSE 0 END) as transfer_total,
                SUM(CASE WHEN payment_type = 'CORPORATE' THEN total_amount ELSE 0 END) as corporate_total,
                SUM(total_amount) as grand_total
            FROM orders 
            WHERE status IN ('COMPLETE', 'READY', 'ON_DELIVERY')
            GROUP BY date
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First revert the view
        DB::statement("
            CREATE OR REPLACE VIEW daily_payment_reports AS
            SELECT 
                MIN(id) as id,
                DATE(created_at) as date,
                SUM(CASE WHEN payment_type = 'TUNAI' THEN total_amount ELSE 0 END) as tunai_total,
                SUM(CASE WHEN payment_type = 'QRIS' THEN total_amount ELSE 0 END) as qris_total,
                SUM(CASE WHEN payment_type = 'TRANSFER' THEN total_amount ELSE 0 END) as transfer_total,
                SUM(total_amount) as grand_total
            FROM orders 
            WHERE status IN ('COMPLETE', 'READY', 'ON_DELIVERY')
            GROUP BY date
        ");

        // Reverting ENUM is dangerous if there's data, so we change CORPORATE data to TUNAI first it
        DB::table('orders')->where('payment_type', 'CORPORATE')->update(['payment_type' => 'TUNAI']);

        // Now revert the Enum
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_type ENUM('TUNAI','QRIS','TRANSFER') NOT NULL");
    }
};
