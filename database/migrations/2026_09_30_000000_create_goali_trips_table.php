<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goali_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('travel_date');
            $table->time('travel_time');
            $table->string('status', 20);
            $table->string('solicitation', 100);
            $table->string('origin');
            $table->string('destination');
            $table->string('passenger');
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->index(['travel_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goali_trips');
    }
};