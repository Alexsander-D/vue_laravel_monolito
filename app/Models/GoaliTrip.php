<?php

namespace App\Models;

use App\Models\Spatie\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoaliTrip extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'passenger_id',
        'passenger_ids',
        'driver_id',
        'in_progress_started_at',
        'cancelled_by_name',
        'cancelled_by_role',
        'cancelled_at',
        'travel_date',
        'travel_time',
        'status',
        'trip_status',
        'solicitation',
        'trip_code',
        'origin',
        'destination',
        'passenger',
        'observation',
        'responsible',
        'amount',
        'company_profit',
        'duplicate_hash',
    ];

    protected $casts = [
        'travel_date' => 'date:Y-m-d',
        'in_progress_started_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'amount' => 'decimal:2',
        'company_profit' => 'decimal:2',
        'passenger_ids' => 'array',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(GoaliCompany::class, 'company_id');
    }

    public function passengerRecord(): BelongsTo
    {
        return $this->belongsTo(GoaliPassenger::class, 'passenger_id');
    }
}
