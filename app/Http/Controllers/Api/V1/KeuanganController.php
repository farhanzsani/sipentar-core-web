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
     * @OA\Get(
     *     path="/api/v1/keuangan/hpp",
     *     tags={"Keuangan"},
     *     summary="Kalkulasi HPP (Harga Pokok Produksi) - Pemilik only",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="lubang",
     *         in="query",
     *         description="Jumlah lubang tanam",
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="panen_id",
     *         in="query",
     *         description="ID panen (lubang diambil dari budidaya.jumlah_tanaman)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kalkulasi HPP berhasil. Jika tidak ada parameter, gunakan total kapasitas_lubang semua meja",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="lubang", type="integer", example=100),
     *                 @OA\Property(property="hpp_per_lubang", type="string", example="1200"),
     *                 @OA\Property(property="total_hpp", type="string", example="120000.00")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error (panen tidak ditemukan)"),
     *     @OA\Response(response=403, description="Pegawai tidak memiliki akses")
     * )
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
     * @OA\Get(
     *     path="/api/v1/keuangan/alokasi-kas",
     *     tags={"Keuangan"},
     *     summary="Alokasi kas operasional per tanggal (Pemilik only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="tanggal",
     *         in="query",
     *         required=true,
     *         description="Tanggal spesifik (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date", example="2026-11-22")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data alokasi kas berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="tanggal", type="string", format="date", example="2026-11-22"),
     *                 @OA\Property(property="total_pemasukan", type="string", example="1500000.00"),
     *                 @OA\Property(property="total_kas_operasional", type="string", example="550000.00")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error (tanggal wajib diisi)"),
     *     @OA\Response(response=403, description="Pegawai tidak memiliki akses")
     * )
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
     * @OA\Get(
     *     path="/api/v1/keuangan/laporan",
     *     tags={"Keuangan"},
     *     summary="Laporan keuangan per periode (Pemilik only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         required=true,
     *         description="Tanggal mulai (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date", example="2026-11-01")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         required=true,
     *         description="Tanggal akhir (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date", example="2026-11-30")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Laporan keuangan berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="periode", type="object",
     *                     @OA\Property(property="from", type="string", format="date", example="2026-11-01"),
     *                     @OA\Property(property="to", type="string", format="date", example="2026-11-30")
     *                 ),
     *                 @OA\Property(property="total_pemasukan", type="string", example="2000000.00"),
     *                 @OA\Property(property="total_pengeluaran", type="string", example="800000.00"),
     *                 @OA\Property(property="total_kas_operasional", type="string", example="700000.00"),
     *                 @OA\Property(property="total_laba_bersih", type="string", example="1300000.00"),
     *                 @OA\Property(property="selisih", type="string", example="1200000.00", description="Pemasukan - Pengeluaran")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error (from & to wajib diisi)"),
     *     @OA\Response(response=403, description="Pegawai tidak memiliki akses")
     * )
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
