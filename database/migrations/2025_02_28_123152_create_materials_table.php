<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screening_id')->constrained('screening')->onDelete('cascade');
            $table->string('deadline_list');
            $table->string('material_output');
            $table->date('expected_arrival'); 
            $table->string('status');
            $table->string('type_transport')->nullable()->default('');
            $table->string('nf', 255)->nullable()->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
