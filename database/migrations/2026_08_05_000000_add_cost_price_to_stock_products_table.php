<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stock_products', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->default(0)->after('quantity');
        });
    }

    public function down()
    {
        Schema::table('stock_products', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
