<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Budidaya;
use App\Models\Panen;
use Illuminate\Foundation\Http\FormRequest;

class PanenStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'budidaya_id' => ['required', 'integer', 'exists:budidayas,id'],
            'tanggal_panen' => ['required', 'date'],
            'berat_kg' => ['nullable', 'required_if:status,selesai', 'numeric', 'min:0.01'],
            'status' => ['required', 'string', 'in:terjadwal,selesai'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                // Validate tanggal_panen >= budidaya.tanggal_semai
                if ($this->filled('budidaya_id') && $this->filled('tanggal_panen')) {
                    $budidaya = Budidaya::find($this->budidaya_id);
                    
                    if ($budidaya && $this->tanggal_panen < $budidaya->tanggal_semai->format('Y-m-d')) {
                        $validator->errors()->add(
                            'tanggal_panen',
                            'Tanggal panen tidak boleh lebih awal dari tanggal semai.'
                        );
                    }

                    // Check unique meja_id + tanggal_panen
                    if ($budidaya) {
                        $existingPanen = Panen::whereHas('budidaya', function ($query) use ($budidaya) {
                            $query->where('meja_id', $budidaya->meja_id);
                        })
                        ->where('tanggal_panen', $this->tanggal_panen)
                        ->exists();

                        if ($existingPanen) {
                            $validator->errors()->add(
                                'tanggal_panen',
                                'Sudah ada panen terjadwal untuk meja ini pada tanggal yang sama.'
                            );
                        }
                    }
                }
            }
        ];
    }

    public function messages(): array
    {
        return [
            'budidaya_id.required' => 'Budidaya ID harus diisi.',
            'budidaya_id.exists' => 'Budidaya tidak ditemukan.',
            'tanggal_panen.required' => 'Tanggal panen harus diisi.',
            'tanggal_panen.date' => 'Tanggal panen harus berupa tanggal yang valid.',
            'berat_kg.required_if' => 'Berat kg harus diisi untuk status selesai.',
            'berat_kg.numeric' => 'Berat kg harus berupa angka.',
            'berat_kg.min' => 'Berat kg minimal 0.01.',
            'status.required' => 'Status harus diisi.',
            'status.in' => 'Status harus berupa terjadwal atau selesai.',
        ];
    }
}
