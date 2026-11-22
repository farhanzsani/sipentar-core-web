<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StokStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_bahan' => ['required', 'string', 'max:100'],
            'kategori' => ['required', 'string', 'max:50'],
            'satuan' => ['required', 'string', 'max:20'],
            'jumlah_stok' => ['required', 'numeric', 'min:0'],
            'batas_minimum' => ['required', 'numeric', 'min:0'],
        ];
    }
}
