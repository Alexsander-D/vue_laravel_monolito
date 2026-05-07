<?php

namespace App\Models\Registration;

use App\Models\Registration\Components;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefectsSolutions extends Model
{
    use HasFactory;

    // Especifica a tabela se necessário (opcional)
    protected $table = 'defects_solutions';

    // Define as colunas que podem ser preenchidas em massa
    protected $fillable = [
        'id',
        'user_id',
        'components_id',
        'defect',
        'solution',
        'created_at',
        'updated_at',
    ];

    /**
     * Obtém o componente ao qual esta solução de defeito pertence.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function component()
    {
        return $this->belongsTo(Components::class, 'components_id');
    }

    /**
     * Obtém as entradas nas filas.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getDefectsSolutionsOnComponentAndFamily()
    {
        return DefectsSolutions::select(
            'defects_solutions.id',
            'components.component',
            'components.family',
            'defects_solutions.defect',
            'defects_solutions.solution',
        )
            ->leftJoin('components', 'components.id', '=', 'defects_solutions.components_id')
            ->get();
    }
}
