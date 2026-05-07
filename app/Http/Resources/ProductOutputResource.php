<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOutputResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->entry->created_at?->format('d/m/Y H:i:s'),
            'lote' => $this->product_lot ?? '',
            'responsable' => $this->user->name ?? '',
            'product' => $this->product_new ?? '',
            'serial_number' => $this->serial_number ?? '',
            'updated_at' => $this->updated_at?->format('d/m/Y H:i:s'),
            'imei1' => $this->imei1 ?? '',
            'imei2' => $this->imei2 ?? '',

        ];
    }
}
