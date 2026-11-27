<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goali_trips', function (Blueprint $table) {
            $table->dropUnique('goali_trips_duplicate_hash_unique');
            $table->dropColumn('duplicate_hash');
        });
    }

    public function down(): void
    {
        Schema::table('goali_trips', function (Blueprint $table) {
            $table->char('duplicate_hash', 64)->nullable()->unique();
        });
    }
};
