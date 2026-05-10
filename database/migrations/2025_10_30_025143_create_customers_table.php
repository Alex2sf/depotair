<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('phone_number', 20)->unique();
            $table->text('address')->nullable();

            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->index(['latitude', 'longitude']);

            $table->timestamps();
            $table->index('phone_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
