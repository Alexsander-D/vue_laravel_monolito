<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goali_driver_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['team_id', 'manager_id', 'driver_id']);
            $table->index(['team_id', 'driver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goali_driver_assignments');
    }
};
