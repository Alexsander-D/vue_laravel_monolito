<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceServicePrice extends Model
{
    protected $fillable = [
        'name',
        'price',
        'icon',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public static function catalog(): array
    {
        return static::query()
            ->orderBy('sort_order')
            ->get(['name', 'price', 'icon'])
            ->map(fn (self $service) => [
                'name' => $service->name,
                'price' => (float) $service->price,
                'icon' => $service->icon,
            ])
            ->all();
    }
}
