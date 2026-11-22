<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\KeuanganService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class KeuanganController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected KeuanganService $keuanganService
    ) {}

    /**
     * Calculate HPP (Harga Pokok Produksi)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function hpp(Request $request): JsonResponse
    {
        $request->validate([
            'lubang' => ['nullable', 'integer', 'min:1'],
            'panen_id' => ['nullable', 'integer', 'exists:panens,id'],
        ], [
            'lubang.integer' => 'Lubang harus berupa angka.',
            'lubang.min' => 'Lubang minimal 1.',
            'panen_id.exists' => 'Data panen tidak ditemukan.',
        ]);

        try {
            $result = $this->keuanganService->calculateHpp(
                $request->lubang,
                $request->panen_id
            );

            return $this->successResponse($result, 'Kalkulasi HPP berhasil.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get alokasi kas operasional for specific date
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function alokasiKas(Request $request): JsonResponse
    {
        $request->validate([
            'tanggal' => ['required', 'date'],
        ], [
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Format tanggal tidak valid.',
        ]);

        $result = $this->keuanganService->getAlokasiKas($request->tanggal);

        return $this->successResponse($result, 'Data alokasi kas berhasil diambil.');
    }

    /**
     * Generate financial report for date range
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function laporan(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ], [
            'from.required' => 'Tanggal mulai wajib diisi.',
            'from.date' => 'Format tanggal mulai tidak valid.',
            'to.required' => 'Tanggal akhir wajib diisi.',
            'to.date' => 'Format tanggal akhir tidak valid.',
            'to.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai.',
        ]);

        $result = $this->keuanganService->getLaporan($request->from, $request->to);

        return $this->successResponse($result, 'Laporan keuangan berhasil dibuat.');
    }
}
