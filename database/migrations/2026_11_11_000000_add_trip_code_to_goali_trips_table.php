<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goali_trips', function (Blueprint $table) {
            $table->string('trip_code')->nullable()->after('solicitation');
        });
    }

    public function down(): void
    {
        Schema::table('goali_trips', function (Blueprint $table) {
            $table->dropColumn('trip_code');
        });
    }
};
