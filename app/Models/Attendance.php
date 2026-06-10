<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'total',
    ];

    public function services()
    {
        return $this->hasMany(AttendanceService::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}