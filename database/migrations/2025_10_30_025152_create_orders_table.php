<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 12)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', [
                'DRAFT',
                'PREPARED',
                'READY',
                'ON_DELIVERY',
                'COMPLETE',
                'CANCELLED'
            ])->default('draft');

            $table->enum('order_type', ['DELIVERY', 'PICKUP']);
            $table->enum('payment_type', ['CASH', 'TRANSFER', 'QRIS']); // Adjust as needed

            $table->text('delivery_address')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->index(['latitude', 'longitude']); // For delivery coordinates

            $table->integer('subtotal')->unsigned()->default(0);
            $table->integer('delivery_fee')->unsigned()->default(0);
            $table->integer('additional_fee')->unsigned()->default(0);
            $table->integer('total_amount')->unsigned()->default(0);

            $table->text('notes')->nullable();

            // New timestamp fields
            $table->timestamp('delivery_time')->nullable(); // When order is out for delivery
            $table->timestamp('completed_time')->nullable(); // When order is completed

            $table->timestamps();

            $table->index('order_number');
            $table->index('customer_id');
            $table->index('status');
            $table->index('order_type');
            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
