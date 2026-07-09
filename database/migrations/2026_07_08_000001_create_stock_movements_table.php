<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_product_id');
            $table->enum('type', ['entrada', 'baixa']);
            $table->unsignedInteger('quantity');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('stock_product_id')->references('id')->on('stock_products')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_movements');
    }
};
