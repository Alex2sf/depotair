<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
// database/migrations/xxxx_xx_xx_xxxxxx_add_real_quantity_to_inventories_table.php
    public function up()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->integer('real_quantity')->default(0)->after('quantity');
            $table->timestamp('last_opname_at')->nullable()->after('real_quantity');
        });
    }
    public function down()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['real_quantity', 'last_opname_at']);
        });
    }
};
