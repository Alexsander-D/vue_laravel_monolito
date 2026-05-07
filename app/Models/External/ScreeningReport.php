<?php

namespace App\Models\External;

use App\Models\Registration\DefectsSolutions;
use App\Models\Registration\Products;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScreeningReport extends Model
{
    use HasFactory;

    protected $table = 'screening_report';

    protected $fillable = [
        'user_id',
        'screening_id',
        'products_id',
        'imei1',
        'imei2',
        'serial_number',
        'hardware_version',
        'qr_code',
        'include',
        'gemco',
        'seal',
        'fm',
        'UniqueID',
        'patrimony',
        'observation',
        'price',
        'guarantee',
        'status'
    ];
    
    /**
     * Relacionamento com a tabela de produtos
     *
     * @return BelongsTo
     */
    public function products()
    {
        return $this->belongsTo(Products::class, 'products_id');
    }

    /**
     * Acessor para recuperar o nome do produto
     *
     * @return string|null
     */
    public function getProductNameAttribute()
    {
        return $this->products ? $this->products->name : null;
    }

    /**
     * Relacionamento com a tabela de triagens
     *
     * @return BelongsTo
     */
    public function screening()
    {
        return $this->belongsTo(Screening::class, 'screening_id');
    }

    /**
     * Relacionamento com a tabela de Defeitos e Solu es
     *
     * @return BelongsTo
     */
    public function defectsSolutions()
    {
        return $this->belongsTo(DefectsSolutions::class, 'defects_solutions_id');
    }


    /**
     * Retorna um array com a quantidade de produtos agrupados por sku e status de garantia
     * 
     * @param Builder $query
     * @param int $screeningId
     * @return Collection
     */
    public function scopeGetProductsByScreening($query, $screeningId)
    {
        return $query->with('products')
            ->where('screening_id', $screeningId)
            ->get()
            ->groupBy(function ($entry) {
                if ($entry->products) {
                    return $entry->products->sku . '-' . ($entry->guarantee === 'em garantia' ? 'em garantia' : 'fora de garantia');
                }
                return null; // Retorna null se não houver produto
            })
            ->map(function ($groupedEntries, $key) {
                if ($groupedEntries->isNotEmpty() && $groupedEntries->first()->products) {
                    [$sku, $guaranteeStatus] = explode('-', $key);

                    $statusCounts = [
                        'recuperado' => 0,
                        'devolucao' => 0,
                        'em garantia' => 0,
                        'mau uso' => 0,
                        'nao encontrado' => 0,
                        'proxima triagem' => 0,
                    ];

                    $totalQuantity = 0;

                    foreach ($groupedEntries as $entry) {
                        $totalQuantity++;
                        $status = $entry->status ?? 'N/A';
                        if (array_key_exists($status, $statusCounts)) {
                            $statusCounts[$status]++;
                        }
                    }

                    return [
                        'product' => $sku,
                        'family' => $groupedEntries->first()->products->family ?? 'N/A',
                        'warranty' => strtoupper($guaranteeStatus),
                        'quantity' => $totalQuantity,
                        'status_counts' => $statusCounts,
                        'include' => (bool) ($groupedEntries->first()->include ?? false),
                        'total' => $totalQuantity,
                        'created_at' => $groupedEntries->first()->created_at->format('d/m/Y'),
                    ];
                }
                return null;
            })
            ->filter()
            ->values();
    }

    /**
     * Retorna um array com a quantidade de produtos agrupados por sku
     * 
     * @param Builder $query
     * @param int $screeningId
     * @return Collection
     */
    public function scopeGetProductsGroupedBySku($query, $screeningId)
    {
        return $query->with('products')
            ->where('screening_id', $screeningId)
            ->get()
            ->groupBy(function ($entry) {
    
                return $entry->products ? $entry->products->sku : null;
            })
            ->map(function ($groupedEntries, $key) {

                if ($groupedEntries->isNotEmpty() && $groupedEntries->first()->products) {
                    $statusCounts = [
                        'recuperado' => 0,
                        'devolucao' => 0,
                        'em garantia' => 0,
                        'mau uso' => 0,
                        'nao encontrado' => 0,
                        'proxima triagem' => 0,
                    ];

                    $totalQuantity = 0;

                    foreach ($groupedEntries as $entry) {
                        $totalQuantity++;
                        $status = $entry->status ?? 'N/A';
                        if (array_key_exists($status, $statusCounts)) {
                            $statusCounts[$status]++;
                        }
                    }

                    return [
                        'product' => $key,
                        'family' => $groupedEntries->first()->products->family ?? 'N/A',
                        'quantity' => $totalQuantity,
                        'status_counts' => $statusCounts,
                        'include' => (bool) ($groupedEntries->first()->include ?? false),
                        'total' => $totalQuantity,
                        'created_at' => $groupedEntries->first()->created_at->format('d/m/Y'),
                    ];
                }
                return null;
            })
            ->filter()
            ->values();
    }
}
