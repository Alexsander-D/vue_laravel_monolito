<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goali_trips', function (Blueprint $table) {
            $table->text('passenger')->change();
            $table->json('passenger_ids')->nullable()->after('passenger_id');
        });
    }

    public function down(): void
    {
        Schema::table('goali_trips', function (Blueprint $table) {
            $table->dropColumn('passenger_ids');
            $table->string('passenger', 255)->change();
        });
    }
};
