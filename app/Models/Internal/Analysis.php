<?php

namespace App\Models\Internal;

use App\Models\Registration\DefectsSolutions;
use App\Models\Spatie\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Analysis extends Model
{
    use HasFactory;

    // Especifica a tabela se necessário (opcional)
    protected $table = 'analysis';

    // Define as colunas que podem ser preenchidas em massa
    protected $fillable = ['id', 'queue_id', 'user_id', 'defect_solution_id', 'observation', 'status', 'created_at', 'updated_at'];

    // Se não estiver usando timestamps automáticos
    // public $timestamps = true;

    public function queue()
    {
        return $this->belongsTo(Queue::class, 'queue_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function defectSolution()
    {
        return $this->belongsTo(DefectsSolutions::class, 'defect_solution_id');
    }

    public function scopeWithQueueByEntriesWithAnalyzes($query)
    {
        $user = Auth::user();

        return $query
            ->select(
                'analysis.id AS analysis_id',
                'queue.id AS queue_id',
                'queue.updated_at',
                'entry.unique_id',
                'entry.team_id',
                'queue.product',
                'queue.product_new',
                'queue.product_lot',
                'queue.serial_number',
                'queue.imei1',
                'queue.imei2',
                'components.component',
                'defects_solutions.id AS defects_solutions_id',
                'defects_solutions.defect',
                'defects_solutions.solution',
                'queue.status',
                'users.name AS user_name'
            )
            ->leftJoin('queue', 'analysis.queue_id', '=', 'queue.id')
            ->leftJoin('entry', 'queue.entry_id', '=', 'entry.id')
            ->leftJoin('defects_solutions', 'analysis.defect_solution_id', '=', 'defects_solutions.id')
            ->leftJoin('components', 'defects_solutions.components_id', '=', 'components.id')
            ->leftJoin('users', 'queue.user_id', '=', 'users.id')
            ->where('queue.status', 'ANALISE')
            ->where('entry.team_id', $user->currentTeam->id)
            ->orderBy('queue.updated_at', 'desc');
    }
}
