<?php

namespace App\Models\Registration;

use App\Models\Internal\Queue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    use HasFactory;

    // Especifica a tabela se necessário (opcional)
    protected $table = 'products';

    // Define as colunas que podem ser preenchidas em massa
    protected $fillable = [
        'id',
        'user_id',
        'family',
        'ean',
        'sku',
        'description',
        'type',
        'line',
        'group',
        'sub_group',
        'price',
        'customization'
    ];

    /**
     * Retorna a fila relacionada ao produto.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function queue()
    {
        return $this->hasOne(Queue::class, 'product_new', 'sku');
    }
}
