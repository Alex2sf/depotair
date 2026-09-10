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
        if (!Schema::hasTable('cashier_shifts')) {
            Schema::create('cashier_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->dateTime('start_time');
                $table->dateTime('end_time')->nullable();
                $table->bigInteger('starting_cash')->default(0); // Saldo awal laci saat shift dibuka
                $table->bigInteger('cash_sales')->default(0); // Total penjualan tunai selama shift
                $table->bigInteger('cash_expenses')->default(0); // Total pengeluaran laci selama shift
                $table->bigInteger('cash_deposited')->default(0); // Total setoran dari laci ke kas besar selama shift
                $table->bigInteger('expected_cash')->default(0); // starting + sales - expenses - deposited
                $table->bigInteger('actual_cash')->nullable(); // Uang fisik aktual dihitung kasir saat tutup shift
                $table->bigInteger('difference')->default(0); // actual_cash - expected_cash (bisa plus / minus)
                $table->text('notes')->nullable();
                $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN');
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['start_time', 'end_time']);
            });
        }

        if (Schema::hasTable('cash_transactions')) {
            Schema::table('cash_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('cash_transactions', 'proof_image')) {
                    $table->string('proof_image', 255)->nullable()->after('description');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cash_transactions') && Schema::hasColumn('cash_transactions', 'proof_image')) {
            Schema::table('cash_transactions', function (Blueprint $table) {
                $table->dropColumn('proof_image');
            });
        }

        Schema::dropIfExists('cashier_shifts');
    }
};
