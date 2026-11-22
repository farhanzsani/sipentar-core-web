<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenggunaanOperasionalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'stok_id' => $this->stok_id,
            'tanggal' => $this->tanggal->format('Y-m-d'),
            'jumlah' => $this->jumlah,
            'keterangan' => $this->keterangan,
            'user' => new UserResource($this->whenLoaded('user')),
            'stok' => new StokResource($this->whenLoaded('stok')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
