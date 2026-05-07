<?php

namespace App\Models\Internal;

use App\Models\Spatie\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOutput extends Model
{
    use HasFactory;

    // Especifica a tabela se necessário (opcional)
    protected $table = 'product_output';

    // Define as colunas que podem ser preenchidas em massa
    protected $fillable = [
        'id',
        'user_id',
        'queue_id',
        'end_process',
        'created_at',
        'updated_at',
    ];

    /**
     * Retorna a fila relacionada a essa sa da de produto
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function queue()
    {
        return $this->belongsTo(Queue::class, 'queue_id');
    }

    /**
     * Retorna o usuario que realizou a saida do produto
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    /**
     * Retorna todas as embalagens feitas hoje
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getAllDailyPacks()
    {
        return ProductOutput::select(
            'product_output.updated_at',
            'entry.unique_id'
        )
            ->leftJoin('queue', 'queue.id', '=', 'product_output.queue_id')
            ->leftJoin('entry', 'entry.id', '=', 'queue.entry_id')
            ->whereDate('product_output.updated_at', '=', now()->toDateString())
            ->get();
    }
}
