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
        Schema::create('components', function (Blueprint $table) {
            $table->id(); // This is the AUTO_INCREMENT column and the primary key
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('component');
            $table->string('family');
            $table->timestamps();

            $table->unique(['component', 'family']);
        });

        Schema::create('defects_solutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('components_id')->constrained('components')->onDelete('cascade');
            $table->string('defect');
            $table->string('solution');
            $table->timestamps();

            $table->unique(['components_id', 'defect', 'solution']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defects_solutions');
        Schema::dropIfExists('components');
    }
};
