<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PanenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'budidaya_id' => $this->budidaya_id,
            'tanggal_panen' => $this->tanggal_panen?->format('Y-m-d'),
            'berat_kg' => $this->berat_kg,
            'status' => $this->status,
            'budidaya' => new BudidayaResource($this->whenLoaded('budidaya')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
