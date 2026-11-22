<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaksiKeuanganResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'panen_id' => $this->panen_id,
            'jenis' => $this->jenis,
            'tanggal' => $this->tanggal->format('Y-m-d'),
            'jumlah' => $this->jumlah,
            'persentase_kas' => $this->persentase_kas,
            'kas_operasional' => $this->kas_operasional,
            'laba_bersih' => $this->laba_bersih,
            'keterangan' => $this->keterangan,
            'user' => new UserResource($this->whenLoaded('user')),
            'panen' => new PanenResource($this->whenLoaded('panen')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
