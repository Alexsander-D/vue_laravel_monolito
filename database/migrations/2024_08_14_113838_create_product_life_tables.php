<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tabela entry
        Schema::create('entry', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();
        });

        // Tabela queue
        Schema::create('queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('entry')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('product')->nullable();
            $table->string('product_new')->nullable();
            $table->string('product_lot')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('imei1')->nullable();
            $table->string('imei2')->nullable();
            $table->enum('status', ['PENDENTE', 'ANALISE', 'DESCARTE', 'RECUPERADO']);
            $table->timestamps();
        });

        // Tabela report
        Schema::create('report', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained('queue')->onDelete('cascade');
            $table->foreignId('defect_solution_id')->constrained('defects_solutions')->onDelete('cascade');
            $table->string('observation')->nullable();
            $table->timestamps();
            $table->unique(['queue_id', 'defect_solution_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('report');
        Schema::dropIfExists('queue');
        Schema::dropIfExists('entry');
    }
};
