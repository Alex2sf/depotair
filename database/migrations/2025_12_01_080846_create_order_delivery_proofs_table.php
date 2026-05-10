<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // database/migrations/xxxx_xx_xx_xxxxxx_create_order_delivery_proofs_table.php
    public function up()
    {
        Schema::create('order_delivery_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('image_url');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('order_id');
        });
    }
    public function down()
    {
        Schema::dropIfExists('order_delivery_proofs');
    }
};
