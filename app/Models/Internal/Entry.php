<?php

namespace App\Models\Internal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Entry extends Model
{
    use HasFactory;

    // Especifica a tabela se necessário (opcional)
    protected $table = 'entry';

    // Define as colunas que podem ser preenchidas em massa
    protected $fillable = ['unique_id', 'user_id', 'team_id', 'created_at', 'updated_at'];

    // Se não estiver usando timestamps automáticos
    public $timestamps = true;

    /**
     * Retorna todas as filas relacionadas a entrada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function queue()
    {
        return $this->hasMany(Queue::class, 'entry_id');
    }

    /**
     * Retorna todas as entradas na fila do usuario especificado
     *
     * @param  int  $userId
     * @return \Illuminate\Support\Collection
     */
    public static function getEntriesOnQueues($userId = null)
    {
        $currentTeamId = Auth::user()->currentTeam->id;

        return self::select(
            'entry.id',
            'entry.unique_id',
            'entry.created_at as entries_created_at',
            'users.name',
            'queue.created_at as queue_created_at',
            'queue.id as queue_id',
            'queue.product'
        )
            ->leftJoin('queue', 'entry.id', '=', 'queue.entry_id')
            ->leftJoin('users', 'queue.user_id', '=', 'users.id')
            ->where('entry.team_id', $currentTeamId)
            ->where('queue.status', 'LIKE', 'PENDENTE')
            ->when($userId, function ($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->where('queue.user_id', $userId)
                        ->orWhereNull('queue.user_id');
                });
            })
            ->get();
    }

    /**
     * Retorna o id da entrada com base no unique_id.
     *
     * @param int $unique_id
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public static function getQueuesIdByEntry(int $unique_id)
    {
        return self::select(
            'id',
        )
            ->where('unique_id', $unique_id)
            ->first();
    }
}
