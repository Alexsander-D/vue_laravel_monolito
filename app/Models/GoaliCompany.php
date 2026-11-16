<?php

namespace App\Models;

use App\Models\Spatie\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoaliCompany extends Model
{
    protected $fillable = [
        'team_id',
        'name',
        'departure_address',
        'phone',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(GoaliPassenger::class, 'company_id');
    }
}
