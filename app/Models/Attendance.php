<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'total',
        'payment_method',
    ];

    public function scopeDateRange($query, ?string $startDate = null, ?string $endDate = null)
    {
        if (! empty($startDate)) {
            $query->whereDate('attendances.created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if (! empty($endDate)) {
            $query->whereDate('attendances.created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        if (empty($startDate) && empty($endDate)) {
            $query->whereDate('attendances.created_at', Carbon::today());
        }

        return $query;
    }

    public function services()
    {
        return $this->hasMany(AttendanceService::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}