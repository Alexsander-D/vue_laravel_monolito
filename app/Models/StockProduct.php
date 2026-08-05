<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockProduct extends Model
{
    use HasFactory;

    protected $table = 'stock_products';

    protected $fillable = [
        'product_name',
        'quantity',
        'cost_price',
        'price',
        'user_id',
    ];

    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'stock_product_id');
    }
}
