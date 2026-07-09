<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $table = 'stock_movements';

    protected $fillable = [
        'stock_product_id',
        'type',
        'quantity',
        'price',
        'user_id',
        'description',
    ];

    public function stockProduct()
    {
        return $this->belongsTo(StockProduct::class, 'stock_product_id');
    }
}
