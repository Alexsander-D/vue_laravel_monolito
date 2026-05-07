<?php

namespace App\Models\Internal;

use App\Models\Spatie\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProductTransfer extends Model
{
    // Especifica a tabela se necessário (opcional)
    protected $table = 'product_transfer';

    // Define as colunas que podem ser preenchidas em massa
    protected $fillable = [
        'id',
        'created_at',
        'sent_by',
        'queue_id',
        'received_by',
        'updated_at',
        'status'
    ];

    /**
     * Rela o de pertencimento para a fila do produto.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function queue()
    {
        return $this->belongsTo(Queue::class, 'queue_id');
    }

    /**
     * Retorna o usu rio que enviou o produto.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Retorna o usu rio que recebeu o produto.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Filtra as transfer ncias de produtos com a rela o de fila por um id de usu rio e pela data atual.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithQueueAndUserIdAndActualDate($query, $userId)
    {
        return $query->with(['queue'])
            ->where(function ($q) use ($userId) {
                $q->where('sent_by', $userId)
                    ->whereDate('created_at', now());
            })
            ->orWhere(function ($q) use ($userId) {
                $q->where('received_by', $userId)
                    ->where('status', 'AGUARDANDO CONFIRMACAO');
            });
    }


    /**
     * Retorna todas as transfer ncias de produtos com a rela o de fila
     * para o m s e ano atuais.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getWithQueueAndActualDate()
    {
        return
            $this->with(['queue'])
            ->whereMonth('updated_at', Carbon::now()->month)
            ->whereYear('updated_at', Carbon::now()->year)
            ->get();
    }


    /**
     * Retorna todas as transfer ncias de produtos com a rela o de fila
     * para o m s e ano atuais.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function scopeWithQueueAndActualDate($query)
    {
        return
            $query->with(['queue'])
            ->whereMonth('updated_at', Carbon::now()->month)
            ->whereYear('updated_at', Carbon::now()->year)
            ->get();
    }
}
