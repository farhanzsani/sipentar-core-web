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
use OpenApi\Attributes as OA;

class TransaksiKeuanganController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/v1/transaksi-keuangan',
        summary: 'Daftar transaksi keuangan (Pemilik only)',
        security: [['bearerAuth' => []]],
        tags: ['Transaksi Keuangan'],
        parameters: [
            new OA\Parameter(
                name: 'jenis',
                in: 'query',
                description: 'Filter berdasarkan jenis',
                schema: new OA\Schema(type: 'string', enum: ['pemasukan', 'pengeluaran'])
            ),
            new OA\Parameter(
                name: 'from',
                in: 'query',
                description: 'Tanggal mulai (YYYY-MM-DD)',
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'to',
                in: 'query',
                description: 'Tanggal akhir (YYYY-MM-DD)',
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Jumlah data per halaman',
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Pegawai tidak memiliki akses')
        ]
    )]
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

    #[OA\Post(
        path: '/api/v1/transaksi-keuangan',
        summary: 'Catat transaksi keuangan baru (Pemilik only)',
        security: [['bearerAuth' => []]],
        tags: ['Transaksi Keuangan'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['jenis', 'tanggal', 'jumlah'],
                properties: [
                    new OA\Property(property: 'jenis', type: 'string', enum: ['pemasukan', 'pengeluaran'], example: 'pemasukan'),
                    new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-11-22'),
                    new OA\Property(property: 'jumlah', type: 'number', format: 'decimal', example: 1000000.00),
                    new OA\Property(property: 'persentase_kas', type: 'number', format: 'decimal', example: 35.00, description: 'Wajib untuk pemasukan (30-40)'),
                    new OA\Property(property: 'panen_id', type: 'integer', example: 1),
                    new OA\Property(property: 'keterangan', type: 'string', example: 'Penjualan panen November')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Transaksi berhasil dibuat. Server auto-calculate kas_operasional & laba_bersih'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 403, description: 'Pegawai tidak memiliki akses')
        ]
    )]
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

    #[OA\Get(
        path: '/api/v1/transaksi-keuangan/{id}',
        summary: 'Detail transaksi keuangan (Pemilik only)',
        security: [['bearerAuth' => []]],
        tags: ['Transaksi Keuangan'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data berhasil diambil'),
            new OA\Response(response: 404, description: 'Transaksi tidak ditemukan'),
            new OA\Response(response: 403, description: 'Pegawai tidak memiliki akses')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $transaksi = TransaksiKeuangan::with(['user', 'panen'])->findOrFail($id);

        return $this->successResponse(
            new TransaksiKeuanganResource($transaksi),
            'Data transaksi keuangan berhasil diambil.'
        );
    }

    #[OA\Put(
        path: '/api/v1/transaksi-keuangan/{id}',
        summary: 'Update transaksi keuangan dengan recalculation (Pemilik only)',
        security: [['bearerAuth' => []]],
        tags: ['Transaksi Keuangan'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['jenis', 'tanggal', 'jumlah'],
                properties: [
                    new OA\Property(property: 'jenis', type: 'string', enum: ['pemasukan', 'pengeluaran']),
                    new OA\Property(property: 'tanggal', type: 'string', format: 'date'),
                    new OA\Property(property: 'jumlah', type: 'number', format: 'decimal'),
                    new OA\Property(property: 'persentase_kas', type: 'number', format: 'decimal'),
                    new OA\Property(property: 'panen_id', type: 'integer'),
                    new OA\Property(property: 'keterangan', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Transaksi berhasil diperbarui dengan recalculation'),
            new OA\Response(response: 404, description: 'Transaksi tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 403, description: 'Pegawai tidak memiliki akses')
        ]
    )]
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
