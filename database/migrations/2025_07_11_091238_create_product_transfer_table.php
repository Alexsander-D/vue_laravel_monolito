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
        Schema::create('product_transfer', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('sent_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('queue_id')->constrained('queue')->onDelete('cascade');
            $table->foreignId('received_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['AGUARDANDO CONFIRMACAO', 'RECEBIDO'])->default('AGUARDANDO CONFIRMACAO');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_transfer');
    }
};
