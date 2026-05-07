<?php

namespace App\Models\Internal;

use App\Models\Registration\DefectsSolutions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Report extends Model
{
    use HasFactory;

    // Especifica a tabela se necessário (opcional)
    protected $table = 'report';

    // Define as colunas que podem ser preenchidas em massa
    protected $fillable = ['id', 'queue_id', 'defect_solution_id', 'observation', 'created_at', 'updated_at'];

    // Se não estiver usando timestamps automáticos
    // public $timestamps = true;

    /**
     * Retorna as informações de um relatório por id da queue
     *
     * @param int $queueId
     * @return \Illuminate\Support\Collection
     */
    public static function getReportInfoByQueueId($queueId)
    {
        return self::select(
                'report.id',
                'components.id AS component_id',
                'components.component',
                DB::raw("CONCAT(defects_solutions.defect, ' => ', defects_solutions.solution) AS label"),
                'defects_solutions.id AS value'
            )
            ->leftJoin('defects_solutions', 'report.defect_solution_id', '=', 'defects_solutions.id')
            ->leftJoin('components', 'defects_solutions.components_id', '=', 'components.id')
            ->where('report.queue_id', $queueId)
            ->get();
    }

    /**
     * Relacionamento com a tabela de defeitos e solu es (DefectsSolutions)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function defectSolution()
    {
        return $this->belongsTo(DefectsSolutions::class, 'defect_solution_id');
    }

    /**
     * Relacionamento com a tabela de filas (Queue)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function queue()
    {
        return $this->belongsTo(Queue::class, 'queue_id');
    }
}
