<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('goali_trips')
            ->where('trip_status', 'AGUARDANDO ANALISE')
            ->update(['trip_status' => 'Aguardando Analise']);
    }

    public function down(): void
    {
        DB::table('goali_trips')
            ->where('trip_status', 'Aguardando Analise')
            ->update(['trip_status' => 'AGUARDANDO ANALISE']);
    }
};
