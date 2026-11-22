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
     * Display a listing of transaksi keuangan with filters
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
     * Store a newly created transaksi keuangan
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
     * Display the specified transaksi keuangan
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
     * Update the specified transaksi keuangan
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
