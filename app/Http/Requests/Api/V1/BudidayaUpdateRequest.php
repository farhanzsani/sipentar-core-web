<?php

namespace App\Http\Requests\Api\V1;

use App\Models\MejaTanam;
use Illuminate\Foundation\Http\FormRequest;

class BudidayaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meja_id' => ['required', 'integer', 'exists:meja_tanams,id'],
            'tanggal_semai' => ['required', 'date', 'before_or_equal:today'],
            'jumlah_tanaman' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'in:berjalan,selesai'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if ($this->filled('meja_id') && $this->filled('jumlah_tanaman')) {
                    $meja = MejaTanam::find($this->meja_id);
                    
                    if ($meja && $this->jumlah_tanaman > $meja->kapasitas_lubang) {
                        $validator->errors()->add(
                            'jumlah_tanaman',
                            'Jumlah tanaman tidak boleh melebihi kapasitas lubang meja.'
                        );
                    }
                }
            }
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // Auto-fill user_id from authenticated user
        $validated['user_id'] = auth()->id();
        
        return $validated;
    }

    public function messages(): array
    {
        return [
            'meja_id.required' => 'Meja ID harus diisi.',
            'meja_id.exists' => 'Meja tidak ditemukan.',
            'tanggal_semai.required' => 'Tanggal semai harus diisi.',
            'tanggal_semai.date' => 'Tanggal semai harus berupa tanggal yang valid.',
            'tanggal_semai.before_or_equal' => 'Tanggal semai tidak boleh di masa depan.',
            'jumlah_tanaman.required' => 'Jumlah tanaman harus diisi.',
            'jumlah_tanaman.integer' => 'Jumlah tanaman harus berupa angka.',
            'jumlah_tanaman.min' => 'Jumlah tanaman minimal 1.',
            'status.required' => 'Status harus diisi.',
            'status.in' => 'Status harus berupa berjalan atau selesai.',
        ];
    }
}
