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
        Schema::create('analysis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('queue_id');
            $table->unsignedBigInteger('defect_solution_id')->nullable();
            $table->text('observation')->nullable();
            $table->enum('status', ['EM_TRATATIVA', 'DESCARTE', 'RECUPERADO'])->default('EM_TRATATIVA');
            $table->timestamps();

            $table->foreign('queue_id')
                ->references('id')
                ->on('queue')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis');
    }
};
