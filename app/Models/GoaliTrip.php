<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoaliTrip extends Model
{
    protected $fillable = [
        'user_id',
        'travel_date',
        'travel_time',
        'status',
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
}