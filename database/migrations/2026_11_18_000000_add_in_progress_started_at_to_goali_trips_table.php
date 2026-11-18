<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goali_trips', function (Blueprint $table) {
            $table->timestamp('in_progress_started_at')->nullable()->after('trip_status');
        });
    }

    public function down(): void
    {
        Schema::table('goali_trips', function (Blueprint $table) {
            $table->dropColumn('in_progress_started_at');
        });
    }
};
