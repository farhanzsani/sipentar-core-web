<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StokResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_bahan' => $this->nama_bahan,
            'kategori' => $this->kategori,
            'satuan' => $this->satuan,
            'jumlah_stok' => $this->jumlah_stok,
            'batas_minimum' => $this->batas_minimum,
            'is_low' => $this->is_low,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
