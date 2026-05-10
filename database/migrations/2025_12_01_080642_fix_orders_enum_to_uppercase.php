<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // LANGKAH 1: Ubah kolom jadi VARCHAR sementara (biar bisa ganti data)
        Schema::table('orders', function ($table) {
            $table->string('status')->default('DRAFT')->change();
            $table->string('payment_type')->nullable()->change();
        });

        // LANGKAH 2: Update semua data ke nilai baru
        DB::table('orders')
            ->where('status', 'draft')->update(['status' => 'DRAFT']);
        DB::table('orders')
            ->where('status', 'prepared')->update(['status' => 'PREPARED']);
        DB::table('orders')
            ->where('status', 'ready')->update(['status' => 'READY']);
        DB::table('orders')
            ->where('status', 'on_delivery')->update(['status' => 'ON_DELIVERY']);
        DB::table('orders')
            ->where('status', 'complete')->update(['status' => 'COMPLETE']);
        DB::table('orders')
            ->where('status', 'cancelled')->update(['status' => 'CANCELLED']);

        DB::table('orders')
            ->where('payment_type', 'CASH')->update(['payment_type' => 'TUNAI']);
        // Kalau ada yang null atau aneh-aneh, jadi TUNAI aja
        DB::table('orders')
            ->whereNull('payment_type')->orWhere('payment_type', '')->update(['payment_type' => 'TUNAI']);

        // LANGKAH 3: Balikin jadi ENUM dengan nilai baru
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('DRAFT','PREPARED','READY','ON_DELIVERY','COMPLETE','CANCELLED') DEFAULT 'DRAFT' NOT NULL");
        DB::statement("ALTER TABLE orders MODIFY COLUMN order_type ENUM('DELIVERY','PICKUP') NOT NULL");
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_type ENUM('TUNAI','QRIS','TRANSFER') NOT NULL");
    }

    public function down(): void
    {
        Schema::table('orders', function ($table) {
            $table->string('status')->change();
            $table->string('payment_type')->change();
        });

        DB::table('orders')->where('status', 'DRAFT')->update(['status' => 'draft']);
        DB::table('orders')->where('payment_type', 'TUNAI')->update(['payment_type' => 'CASH']);

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft','prepared','ready','on_delivery','complete','cancelled') DEFAULT 'draft'");
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_type ENUM('CASH','TRANSFER','QRIS')");
    }
};