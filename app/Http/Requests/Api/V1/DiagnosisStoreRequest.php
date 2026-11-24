<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class DiagnosisStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'], // Max 5MB
            'budidaya_id' => ['required', 'integer', 'exists:budidayas,id'],
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
            'image.required' => 'Gambar harus diunggah.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Gambar harus berformat jpg, jpeg, atau png.',
            'image.max' => 'Ukuran gambar maksimal 5MB.',
            'budidaya_id.required' => 'Budidaya ID harus diisi.',
            'budidaya_id.integer' => 'Budidaya ID harus berupa angka.',
            'budidaya_id.exists' => 'Budidaya tidak ditemukan.',
        ];
    }
}
