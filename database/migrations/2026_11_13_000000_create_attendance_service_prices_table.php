<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_service_prices', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('price', 10, 2);
            $table->string('icon', 32);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $services = [
            ['name' => 'Corte Social', 'price' => 35, 'icon' => '✂️'],
            ['name' => 'Corte Degradê', 'price' => 40, 'icon' => '💈'],
            ['name' => 'Corte Navalhado', 'price' => 45, 'icon' => '🪒'],
            ['name' => 'Barba', 'price' => 25, 'icon' => '🧔'],
            ['name' => 'Bigode', 'price' => 10, 'icon' => '👨‍🦰'],
            ['name' => 'Pezinho', 'price' => 10, 'icon' => '📏'],
            ['name' => 'Sobrancelha', 'price' => 10, 'icon' => '🔍'],
            ['name' => 'Pigmentação', 'price' => 30, 'icon' => '🎨'],
            ['name' => 'Luzes', 'price' => 110, 'icon' => '💡'],
            ['name' => 'Nevou', 'price' => 150, 'icon' => '❄️'],
        ];

        DB::table('attendance_service_prices')->insert(
            array_map(
                fn (array $service, int $index) => [
                    ...$service,
                    'sort_order' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $services,
                array_keys($services)
            )
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_service_prices');
    }
};
