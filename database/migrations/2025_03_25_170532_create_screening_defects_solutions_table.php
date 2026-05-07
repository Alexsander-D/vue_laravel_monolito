<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('screening_defects_solutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screening_report_id')->constrained('screening_report')->onDelete('cascade');
            $table->foreignId('component_id')->constrained('components')->onDelete('cascade');
            $table->foreignId('defects_solutions_id')->constrained('defects_solutions')->onDelete('cascade');
            $table->string('product')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('screening_defects_solutions');
    }
};
 