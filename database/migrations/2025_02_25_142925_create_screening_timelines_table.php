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
        Schema::create('screening_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screening_id')->constrained('screening')->onDelete('cascade');
            $table->text('description');
            $table->string('responsible');
            $table->string('route');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screening_timelines');
    }
};
