<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index('product_name');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_products');
    }
};
