<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goali_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name');
            $table->string('departure_address')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'name']);
        });

        Schema::create('goali_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('goali_companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('boarding_point')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'company_id']);
        });

        Schema::table('goali_trips', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('user_id')->constrained('goali_companies')->nullOnDelete();
            $table->foreignId('passenger_id')->nullable()->after('company_id')->constrained('goali_passengers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('goali_trips', function (Blueprint $table) {
            $table->dropForeign(['passenger_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn(['passenger_id', 'company_id']);
        });

        Schema::dropIfExists('goali_passengers');
        Schema::dropIfExists('goali_companies');
    }
};
