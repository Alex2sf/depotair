<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_procurements', function (Blueprint $table) {
            $table->id();
            $table->string('procurement_number', 50)->unique();
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedBigInteger('total_estimated_amount')->default(0);
            $table->unsignedBigInteger('total_approved_amount')->default(0);
            $table->string('status', 20)->default('PENDING'); // PENDING, APPROVED, REJECTED, RECEIVED
            $table->text('notes')->nullable();
            $table->text('owner_notes')->nullable();
            $table->string('proof_image')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('stock_procurement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_id')->constrained('stock_procurements')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('current_stock')->default(0);
            $table->integer('quantity_requested');
            $table->unsignedBigInteger('unit_cogs_requested')->default(0);
            $table->unsignedBigInteger('subtotal_requested')->default(0);
            $table->integer('quantity_approved')->nullable();
            $table->unsignedBigInteger('unit_cogs_approved')->nullable();
            $table->unsignedBigInteger('subtotal_approved')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_procurement_items');
        Schema::dropIfExists('stock_procurements');
    }
};
