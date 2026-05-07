<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Components extends Model
{
    use HasFactory;

    protected $table = 'components';

    // Define as colunas que podem ser preenchidas em massa
    protected $fillable = [
        'id',
        'user_id',
        'component',
        'family',
    ];

    /**
     * Obtém os DefectsSolutions relacionados a este componente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function defectsSolutions()
    {
        return $this->hasMany(DefectsSolutions::class, 'components_id');
    }
}
