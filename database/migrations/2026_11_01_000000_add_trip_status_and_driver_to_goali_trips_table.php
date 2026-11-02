<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goali_trips', function (Blueprint $table) {
            $table->string('trip_status', 20)->default('Criada');
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('goali_trips', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn(['trip_status', 'driver_id']);
        });
    }
};
