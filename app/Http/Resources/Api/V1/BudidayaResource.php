<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudidayaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'meja_id' => $this->meja_id,
            'tanggal_semai' => $this->tanggal_semai?->format('Y-m-d'),
            'jumlah_tanaman' => $this->jumlah_tanaman,
            'status' => $this->status,
            'hss' => $this->hss,
            'user' => new UserResource($this->whenLoaded('user')),
            'meja' => new MejaTanamResource($this->whenLoaded('meja')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
