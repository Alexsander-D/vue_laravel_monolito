<?php

namespace App\Models\External;

use App\Models\Spatie\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicalScale extends Model
{
    use HasFactory;

    protected $table = 'technical_scales';

    protected $fillable = [
        'screening_id',
        'user_id',
        'technical',
    ];

    /**
     * Retorna o relacionamento entre a escala técnica e a triagem.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function screening()
    {
        return $this->belongsTo(Screening::class);
    }

    /**
     * Retorna o relacionamento entre a escala técnica e o usuário.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
