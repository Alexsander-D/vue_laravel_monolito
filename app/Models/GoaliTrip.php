<?php

namespace App\Models;

use App\Models\Spatie\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoaliTrip extends Model
{
    protected $fillable = [
        'user_id',
        'driver_id',
        'travel_date',
        'travel_time',
        'status',
        'trip_status',
        'solicitation',
        'origin',
        'destination',
        'passenger',
        'responsible',
        'amount',
    ];

    protected $casts = [
        'travel_date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}