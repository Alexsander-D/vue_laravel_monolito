<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceService extends Model
{
    protected $fillable = [
        'attendance_id',
        'service_name',
        'price',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}