<?php

namespace App\Models\Internal;

use App\Models\Registration\Products;
use App\Models\Spatie\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    use HasFactory;

    protected $table = 'queue';

    protected $fillable = ['entry_id', 'user_id', 'product', 'serial_number', 'imei1', 'imei2', 'product_lot', 'status', 'created_at', 'updated_at', 'is_misuse'];

    /**
     * Relacionamento com a entrada (Entry) da fila.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entry()
    {
        return $this->belongsTo(Entry::class, 'entry_id'); // 'entry_id' é a chave estrangeira na tabela Queue
    }

    /**
     * Relacionamento com o usuário (User) que está na fila.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento com a tabela de relatórios (Reports) da fila.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reports()
    {
        return $this->hasMany(Report::class, 'queue_id');
    }

    /**
     * Relacionamento com a tabela de relatórios (Reports) da fila.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function analysis()
    {
        return $this->hasOne(Analysis::class, 'queue_id');
    }

    /**
     * Relacionamento com a tabela de produtos (Products) da fila.
     *
     * A chave estrangeira é a coluna 'product' na tabela 'queue' e a chave local é a coluna 'sku' na tabela 'products'.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function products()
    {
        return $this->belongsTo(Products::class, 'product', 'sku');
    }

    /**
     * Relacionamento com a tabela de saídas de produtos (ProductOutput) da fila.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function productOutput()
    {
        return $this->hasOne(ProductOutput::class, 'queue_id');
    }

    /**
     * Retorna as informaces de uma fila por id da fila.
     *
     * @param int $queueId
     * @return \Illuminate\Support\Collection
     */
    public static function getQueueInfoByQueueId($queueId)
    {
        return self::select(
            'queue.id AS queue_id',
            'entry.unique_id',
            'queue.status',
            'queue.product AS product_name',
            'products.id AS product_id',
            'products.customization',
            'queue.product_new AS product_new_name',
            'products_new.id AS product_new_id',
            'products.family',
            'components.id AS component_id',
            'components.component AS component_name',
            'defects_solutions.id AS defectSolution_id',
            'defects_solutions.defect',
            'defects_solutions.solution',
            'queue.serial_number',
            'queue.imei1',
            'queue.imei2',
            'report.observation',
            'queue.product_lot',
        )
            ->join('entry', 'queue.entry_id', '=', 'entry.id')
            ->leftJoin('report', 'queue.id', '=', 'report.queue_id')
            ->leftJoin('products', 'products.sku', '=', 'queue.product')
            ->leftJoin('products as products_new', 'products_new.sku', '=', 'queue.product_new')
            ->leftJoin('defects_solutions', 'report.defect_solution_id', '=', 'defects_solutions.id')
            ->leftJoin('components', 'defects_solutions.components_id', '=', 'components.id')
            ->where('queue.id', $queueId)
            ->first();
    }

    /**
     * Cria uma nova fila na SAC com o status especificado.
     *
     * @param int $entryId Id da entrada que ir  ser criada a fila.
     * @param string $status Status da fila.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function createQueueOnSAC($entryId, $status)
    {
        try {
            return self::create([
                'entry_id' => $entryId,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cria uma nova fila na RMA com o status especificado.
     *
     * @param int $entryId Id da entrada que ir  ser criada a fila.
     * @param string $product Produto que ser  criado.
     * @param string $status Status da fila.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function createQueueOnRMA($entryId, $product, $status)
    {
        try {
            return self::create([
                'entry_id' => $entryId,
                'status' => $status,
                'product' => $product,
                'product_new' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Retorna a fila relacionada ao entries_id especificado.
     *
     * Retorna somente a fila que est  com status PENDENTE.
     *
     * @param int $entriesId Id da entrada que ser  buscada a fila.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    public static function getQueueByEntriesId($entriesId)
    {
        $queuesInfo = self::select(
            'id',
            'entry_id',
            'user_id',
            'product',
            'serial_number',
            'imei1',
            'imei2',
            'product_lot',
            'status',
            'created_at',
            'updated_at'
        )
            ->where('entries_id', $entriesId)
            ->where('status', 'LIKE', 'PENDENTE')
            ->get();

        if ($queuesInfo->count() > 1) {
            return response()->json(['error' => 'Mais de um registro encontrado para o entries_id fornecido.'], 500);
        }

        return $queuesInfo->first();
    }

    /**
     * Filtra as filas relacionadas a entrada do usu rio.
     *
     * As filas s o filtradas por data atual e status PENDENTE.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \App\Models\Spatie\User  $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithQueueByEntriesWithTeamAndPendent($query, User $user)
    {
        return $query
            ->select(
                'queue.id',
                'queue.updated_at',
                'entry.unique_id',
                'entry.team_id',
                'queue.product AS product',
                'queue.product_new AS product_new',
                'queue.product_lot',
                'queue.serial_number',
                'queue.imei1',
                'queue.imei2',
                'queue.status'
            )
            ->leftJoin('entry', 'queue.entry_id', '=', 'entry.id')
            ->where('queue.user_id', $user->id)
            ->where(function ($q) {
                $q->whereDate('queue.updated_at', today())
                    ->orWhereIn('queue.status', ['PENDENTE', 'ANALISADO']);
            })
            ->where('entry.team_id', $user->currentTeam->id);
    }


    public function scopeWithQueueByEntriesWithAnalyzes($query, User $user)
    {
        return $query
            ->select(
                'queue.id',
                'queue.updated_at',
                'entry.unique_id',
                'entry.team_id',
                'queue.product AS product',
                'queue.product_new AS product_new',
                'queue.product_lot',
                'queue.serial_number',
                'queue.imei1',
                'queue.imei2',
                'components.component',
                'defects_solutions.defect',
                'defects_solutions.solution',
                'queue.status',
                'users.name AS user_name'
            )
            ->leftJoin('entry', 'queue.entry_id', '=', 'entry.id')
            ->leftJoin('report', 'report.queue_id', '=', 'queue.id')
            ->leftJoin('defects_solutions', 'report.defect_solution_id', '=', 'defects_solutions.id')
            ->leftJoin('components', 'defects_solutions.components_id', '=', 'components.id')
            ->leftJoin('users', 'queue.user_id', '=', 'users.id')
            ->where('queue.status', 'ANALISE')
            ->where('entry.team_id', $user->currentTeam->id)
            ->orderBy('queue.updated_at', 'desc');
    }

    /**
     * Contabiliza as faltas por status de uma fila, agrupadas por status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\Spatie\User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithWidgets($query, User $user)
    {
        return $query
            ->selectRaw('
            COUNT(CASE WHEN queue.status = "PENDENTE" THEN 1 END) AS PENDENTE,
            COUNT(CASE WHEN queue.status = "RECUPERADO" AND DATE(queue.updated_at) = CURDATE() THEN 1 END) AS RECUPERADO,
            COUNT(CASE WHEN queue.status = "DESCARTE" AND DATE(queue.updated_at) = CURDATE() THEN 1 END) AS DESCARTE,
            COUNT(CASE WHEN queue.status IN ("RECUPERADO", "DESCARTE") AND DATE(queue.updated_at) = CURDATE() THEN 1 END) AS TOTAL
        ')
            ->leftJoin('entry', 'queue.entry_id', '=', 'entry.id')
            ->where('queue.user_id', $user->id)
            ->where(function ($q) {
                $q->whereDate('queue.updated_at', today())
                    ->orWhere('queue.status', 'PENDENTE');
            })
            ->where('entry.team_id', $user->currentTeam->id);
    }
}
