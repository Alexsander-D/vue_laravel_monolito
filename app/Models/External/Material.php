<?php

namespace App\Models\External;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'screening_id',
        'deadline_list',
        'material_output',
        'expected_arrival',
        'type_transport',
        'status',
        'nf',
        'observation',
    ];

    /**
     * Retorna o relacionamento entre o material e a triagem.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
   Public function screening()
    {
        return $this->belongsTo(Screening::class, 'screening_id');
    }
}
