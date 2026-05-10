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
        // 1. Expand ENUM to include old and new values
        // We include 'PICKUP' because the migration history suggests it might be there.
        // We include 'TAKEAWAY' because the code was using it.
        DB::statement("ALTER TABLE orders MODIFY COLUMN order_type ENUM('DELIVERY', 'PICKUP', 'TAKEAWAY', 'SELF_PICKUP') NOT NULL");

        // 2. Update existing data
        DB::table('orders')
            ->whereIn('order_type', ['TAKEAWAY', 'PICKUP'])
            ->update(['order_type' => 'SELF_PICKUP']);

        // 3. Finalize ENUM
        DB::statement("ALTER TABLE orders MODIFY COLUMN order_type ENUM('DELIVERY', 'SELF_PICKUP') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Expand ENUM to allow rollback
        DB::statement("ALTER TABLE orders MODIFY COLUMN order_type ENUM('DELIVERY', 'SELF_PICKUP', 'TAKEAWAY') NOT NULL");

        // 2. Revert data
        DB::table('orders')
            ->where('order_type', 'SELF_PICKUP')
            ->update(['order_type' => 'TAKEAWAY']);

        // 3. Finalize ENUM (back to TAKEAWAY as per previous known state in code, ignoring PICKUP for now as code was using TAKEAWAY)
        DB::statement("ALTER TABLE orders MODIFY COLUMN order_type ENUM('DELIVERY', 'TAKEAWAY') NOT NULL");
    }
};
