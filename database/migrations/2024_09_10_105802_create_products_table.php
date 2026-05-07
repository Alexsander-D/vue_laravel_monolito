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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->string('family');
            $table->string('ean');
            $table->string('sku');
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->string('line')->nullable();
            $table->string('group')->nullable();
            $table->string('sub_group')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->tinyInteger('customization')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
