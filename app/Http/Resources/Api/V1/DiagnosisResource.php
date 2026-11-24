<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiagnosisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'budidaya_id' => $this->budidaya_id,
            'image_url' => asset('storage/' . $this->image_path),
            'hasil' => $this->hasil,
            'confidence' => number_format($this->confidence, 2),
            'rekomendasi' => $this->rekomendasi,
            'tanggal_diagnosis' => $this->tanggal_diagnosis?->format('Y-m-d H:i:s'),
            'disclaimer' => 'Hasil ini hanya informasi pendukung, bukan pengganti pemeriksaan langsung.',
            'user' => new UserResource($this->whenLoaded('user')),
            'budidaya' => new BudidayaResource($this->whenLoaded('budidaya')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
