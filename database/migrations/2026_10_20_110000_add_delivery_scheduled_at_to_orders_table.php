<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// File ini pernah dijalankan di database (tercatat di tabel migrations) tapi tidak pernah masuk repo.
// Dibuat ulang dengan nama yang sama; aman dijalankan di database yang kolomnya sudah ada.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'delivery_scheduled_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('delivery_scheduled_at')->nullable()->after('address_link');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'delivery_scheduled_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('delivery_scheduled_at');
            });
        }
    }
};
