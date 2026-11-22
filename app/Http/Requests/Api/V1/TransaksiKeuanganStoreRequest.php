<?php

namespace App\Http\Requests\Api\V1;

use App\Services\KeuanganService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransaksiKeuanganStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $minPercent = env('CASH_ALLOCATION_MIN', 30);
        $maxPercent = env('CASH_ALLOCATION_MAX', 40);

        return [
            'jenis' => ['required', 'string', Rule::in(['pemasukan', 'pengeluaran'])],
            'tanggal' => ['required', 'date'],
            'jumlah' => ['required', 'numeric', 'min:0'],
            'panen_id' => ['nullable', 'integer', 'exists:panens,id'],
            'persentase_kas' => [
                Rule::requiredIf($this->jenis === 'pemasukan'),
                'nullable',
                'numeric',
                "between:$minPercent,$maxPercent"
            ],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get validated data with auto-filled fields
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Auto-fill user_id
        $validated['user_id'] = auth()->id();

        // For pemasukan, calculate kas_operasional and laba_bersih
        if ($validated['jenis'] === 'pemasukan') {
            $keuanganService = app(KeuanganService::class);
            
            $validated['kas_operasional'] = $keuanganService->calculateKasOperasional(
                $validated['jumlah'],
                $validated['persentase_kas']
            );

            $validated['laba_bersih'] = $keuanganService->calculateLabaBersih(
                $validated['jumlah'],
                $validated['kas_operasional']
            );
        } else {
            // For pengeluaran, set nullable fields to null
            $validated['persentase_kas'] = null;
            $validated['kas_operasional'] = null;
            $validated['laba_bersih'] = null;
        }

        return $validated;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'jenis.required' => 'Jenis transaksi wajib diisi.',
            'jenis.in' => 'Jenis transaksi harus pemasukan atau pengeluaran.',
            'tanggal.required' => 'Tanggal transaksi wajib diisi.',
            'tanggal.date' => 'Format tanggal tidak valid.',
            'jumlah.required' => 'Jumlah transaksi wajib diisi.',
            'jumlah.numeric' => 'Jumlah harus berupa angka.',
            'jumlah.min' => 'Jumlah tidak boleh negatif.',
            'panen_id.exists' => 'Data panen tidak ditemukan.',
            'persentase_kas.required' => 'Persentase kas wajib diisi untuk pemasukan.',
            'persentase_kas.numeric' => 'Persentase kas harus berupa angka.',
            'persentase_kas.between' => 'Persentase kas harus antara :min dan :max.',
            'keterangan.max' => 'Keterangan maksimal 255 karakter.',
        ];
    }
}
