<?php

namespace App\Models\Internal;

use App\Models\Spatie\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrackingProtocol extends Model
{
    use HasFactory;

    protected $table = 'tracking_protocol';

    protected $fillable = [
        'id',
        'created_at',
        'updated_at',
        'user_id',
        'tracking',
        'protocol',
        'responsable',
        'received_by',
        'mail',
        'status',
    ];

    /**
     * Returns the user that created the tracking protocol.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
