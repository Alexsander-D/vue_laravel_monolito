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
        Schema::create('tracking_protocol', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tracking')->unique();
            $table->string('responsable');
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->tinyInteger('mail')->default(false);
            $table->enum('status', ['AGUARDANDO CHEGADA', 'SOLICITADO', 'AGUARDANDO CONFIRMACAO',  'ENTREGUE'])->default('AGUARDANDO CHEGADA');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_protocol');
    }
};
