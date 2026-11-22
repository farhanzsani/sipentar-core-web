<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TransaksiKeuanganStoreRequest;
use App\Http\Requests\Api\V1\TransaksiKeuanganUpdateRequest;
use App\Http\Resources\Api\V1\TransaksiKeuanganResource;
use App\Models\TransaksiKeuangan;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransaksiKeuanganController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *     path="/api/v1/transaksi-keuangan",
     *     tags={"Transaksi Keuangan"},
     *     summary="Daftar transaksi keuangan (Pemilik only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="jenis",
     *         in="query",
     *         description="Filter berdasarkan jenis (pemasukan/pengeluaran)",
     *         @OA\Schema(type="string", enum={"pemasukan", "pengeluaran"})
     *     ),
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         description="Tanggal mulai (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         description="Tanggal akhir (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Jumlah data per halaman",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TransaksiKeuangan")),
     *                 @OA\Property(property="links", type="object"),
     *                 @OA\Property(property="meta", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Pegawai tidak memiliki akses")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = TransaksiKeuangan::with(['user', 'panen']);

        // Filter by jenis
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        // Filter by date range (from)
        if ($request->filled('from')) {
            $query->whereDate('tanggal', '>=', $request->from);
        }

        // Filter by date range (to)
        if ($request->filled('to')) {
            $query->whereDate('tanggal', '<=', $request->to);
        }

        // Order by tanggal descending
        $query->orderBy('tanggal', 'desc');

        $perPage = $request->get('per_page', 15);
        $transaksis = $query->paginate($perPage);

        return $this->successResponse(
            TransaksiKeuanganResource::collection($transaksis)->response()->getData(true),
            'Data transaksi keuangan berhasil diambil.'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/transaksi-keuangan",
     *     tags={"Transaksi Keuangan"},
     *     summary="Catat transaksi keuangan baru (Pemilik only)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"jenis", "tanggal", "jumlah"},
     *             @OA\Property(property="jenis", type="string", enum={"pemasukan", "pengeluaran"}, example="pemasukan"),
     *             @OA\Property(property="tanggal", type="string", format="date", example="2026-11-22"),
     *             @OA\Property(property="jumlah", type="number", format="decimal", example=1000000.00),
     *             @OA\Property(property="persentase_kas", type="number", format="decimal", example=35.00, description="Wajib untuk pemasukan (30-40)"),
     *             @OA\Property(property="panen_id", type="integer", example=1, description="Opsional untuk penjualan hasil panen"),
     *             @OA\Property(property="keterangan", type="string", example="Penjualan panen bulan November")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaksi berhasil dibuat. Server auto-calculate kas_operasional & laba_bersih untuk pemasukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/TransaksiKeuangan")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Pegawai tidak memiliki akses")
     * )
     */
    public function store(TransaksiKeuanganStoreRequest $request): JsonResponse
    {
        $transaksi = TransaksiKeuangan::create($request->validated());

        $transaksi->load(['user', 'panen']);

        return $this->successResponse(
            new TransaksiKeuanganResource($transaksi),
            'Transaksi keuangan berhasil dibuat.',
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/transaksi-keuangan/{id}",
     *     tags={"Transaksi Keuangan"},
     *     summary="Detail transaksi keuangan (Pemilik only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID transaksi",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/TransaksiKeuangan")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Transaksi tidak ditemukan"),
     *     @OA\Response(response=403, description="Pegawai tidak memiliki akses")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $transaksi = TransaksiKeuangan::with(['user', 'panen'])->findOrFail($id);

        return $this->successResponse(
            new TransaksiKeuanganResource($transaksi),
            'Data transaksi keuangan berhasil diambil.'
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v1/transaksi-keuangan/{id}",
     *     tags={"Transaksi Keuangan"},
     *     summary="Update transaksi keuangan dengan recalculation (Pemilik only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID transaksi",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"jenis", "tanggal", "jumlah"},
     *             @OA\Property(property="jenis", type="string", enum={"pemasukan", "pengeluaran"}),
     *             @OA\Property(property="tanggal", type="string", format="date"),
     *             @OA\Property(property="jumlah", type="number", format="decimal"),
     *             @OA\Property(property="persentase_kas", type="number", format="decimal", description="Wajib untuk pemasukan"),
     *             @OA\Property(property="panen_id", type="integer"),
     *             @OA\Property(property="keterangan", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaksi berhasil diperbarui dengan recalculation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/TransaksiKeuangan")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Transaksi tidak ditemukan"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Pegawai tidak memiliki akses")
     * )
     */
    public function update(TransaksiKeuanganUpdateRequest $request, int $id): JsonResponse
    {
        $transaksi = TransaksiKeuangan::findOrFail($id);

        $transaksi->update($request->validated());

        $transaksi->load(['user', 'panen']);

        return $this->successResponse(
            new TransaksiKeuanganResource($transaksi),
            'Transaksi keuangan berhasil diperbarui.'
        );
    }
}

/**
 * @OA\Schema(
 *     schema="TransaksiKeuangan",
 *     type="object",
 *     title="Transaksi Keuangan",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="panen_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="jenis", type="string", enum={"pemasukan", "pengeluaran"}, example="pemasukan"),
 *     @OA\Property(property="tanggal", type="string", format="date", example="2026-11-22"),
 *     @OA\Property(property="jumlah", type="string", example="1000000.00"),
 *     @OA\Property(property="persentase_kas", type="string", nullable=true, example="35.00"),
 *     @OA\Property(property="kas_operasional", type="string", nullable=true, example="350000.00", description="Auto-calculated untuk pemasukan"),
 *     @OA\Property(property="laba_bersih", type="string", nullable=true, example="650000.00", description="Auto-calculated untuk pemasukan"),
 *     @OA\Property(property="keterangan", type="string", nullable=true, example="Penjualan panen November"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="user", type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="email", type="string")
 *     ),
 *     @OA\Property(property="panen", type="object", nullable=true,
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="tanggal_panen", type="string", format="date"),
 *         @OA\Property(property="berat_kg", type="string")
 *     )
 * )
 */
