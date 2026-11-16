<?php

namespace App\Models;

use App\Models\Spatie\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoaliPassenger extends Model
{
    protected $fillable = [
        'team_id',
        'company_id',
        'name',
        'boarding_point',
        'phone',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(GoaliCompany::class, 'company_id');
    }
}
