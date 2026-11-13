<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('goali_trips')
            ->where('trip_status', 'Aguardando Analise')
            ->update(['trip_status' => 'Aguardando Aprovação']);

        DB::table('goali_trips')
            ->where('trip_status', 'Finalizada')
            ->update(['trip_status' => 'Pago']);
    }

    public function down(): void
    {
        DB::table('goali_trips')
            ->where('trip_status', 'Aguardando Aprovação')
            ->update(['trip_status' => 'Aguardando Analise']);

        DB::table('goali_trips')
            ->where('trip_status', 'Pago')
            ->update(['trip_status' => 'Finalizada']);
    }
};
