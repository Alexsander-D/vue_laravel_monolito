<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('goali_trips')
            ->where('trip_status', 'Andamento')
            ->update(['trip_status' => 'Em Andamento']);
    }

    public function down(): void
    {
        DB::table('goali_trips')
            ->where('trip_status', 'Em Andamento')
            ->update(['trip_status' => 'Andamento']);
    }
};
