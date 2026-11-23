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
                        new OA\Property(property: 'message', type: 'string', example: 'Data transaksi keuangan berhasil diambil.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                            new OA\Property(property: 'panen_id', type: 'integer', nullable: true, example: 2),
                                            new OA\Property(property: 'jenis', type: 'string', example: 'pemasukan'),
                                            new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-11-22'),
                                            new OA\Property(property: 'jumlah', type: 'string', example: '1500000.00'),
                                            new OA\Property(property: 'persentase_kas', type: 'string', nullable: true, example: '35.00'),
                                            new OA\Property(property: 'kas_operasional', type: 'string', nullable: true, example: '525000.00'),
                                            new OA\Property(property: 'laba_bersih', type: 'string', nullable: true, example: '975000.00'),
                                            new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Penjualan panen November'),
                                            new OA\Property(
                                                property: 'user',
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                                    new OA\Property(property: 'nama', type: 'string', example: 'Pemilik Kebun'),
                                                    new OA\Property(property: 'username', type: 'string', example: 'pemilik'),
                                                    new OA\Property(property: 'role', type: 'string', example: 'pemilik')
                                                ],
                                                type: 'object'
                                            ),
                                            new OA\Property(
                                                property: 'panen',
                                                nullable: true,
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'integer', example: 2),
                                                    new OA\Property(property: 'budidaya_id', type: 'integer', example: 3),
                                                    new OA\Property(property: 'tanggal_panen', type: 'string', format: 'date', example: '2026-12-10'),
                                                    new OA\Property(property: 'berat_kg', type: 'number', nullable: true, example: 12.5),
                                                    new OA\Property(property: 'status', type: 'string', example: 'selesai'),
                                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z'),
                                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z')
                                                ],
                                                type: 'object'
                                            ),
                                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z'),
                                            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z')
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'links',
                                    properties: [
                                        new OA\Property(property: 'first', type: 'string', example: 'http://localhost:8000/api/v1/transaksi-keuangan?page=1'),
                                        new OA\Property(property: 'last', type: 'string', example: 'http://localhost:8000/api/v1/transaksi-keuangan?page=3'),
                                        new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
                                        new OA\Property(property: 'next', type: 'string', nullable: true, example: 'http://localhost:8000/api/v1/transaksi-keuangan?page=2')
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'meta',
                                    properties: [
                                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                        new OA\Property(property: 'last_page', type: 'integer', example: 3),
                                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                        new OA\Property(property: 'total', type: 'integer', example: 42)
                                    ],
                                    type: 'object'
                                )
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Insufficient permissions',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Akses ditolak. Hanya pemilik yang dapat mengakses resource ini.')
                    ]
                )
            )
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
            new OA\Response(
                response: 201,
                description: 'Transaksi berhasil dibuat. Server auto-calculate kas_operasional & laba_bersih',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Transaksi keuangan berhasil dibuat.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'panen_id', type: 'integer', nullable: true, example: 2),
                                new OA\Property(property: 'jenis', type: 'string', example: 'pemasukan'),
                                new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-11-22'),
                                new OA\Property(property: 'jumlah', type: 'string', example: '1500000.00'),
                                new OA\Property(property: 'persentase_kas', type: 'string', nullable: true, example: '35.00'),
                                new OA\Property(property: 'kas_operasional', type: 'string', nullable: true, example: '525000.00'),
                                new OA\Property(property: 'laba_bersih', type: 'string', nullable: true, example: '975000.00'),
                                new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Penjualan panen November'),
                                new OA\Property(
                                    property: 'user',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'nama', type: 'string', example: 'Pemilik Kebun'),
                                        new OA\Property(property: 'username', type: 'string', example: 'pemilik'),
                                        new OA\Property(property: 'role', type: 'string', example: 'pemilik')
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'panen',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 2),
                                        new OA\Property(property: 'budidaya_id', type: 'integer', example: 3),
                                        new OA\Property(property: 'tanggal_panen', type: 'string', format: 'date', example: '2026-12-10'),
                                        new OA\Property(property: 'berat_kg', type: 'number', nullable: true, example: 12.5),
                                        new OA\Property(property: 'status', type: 'string', example: 'selesai'),
                                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z'),
                                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z')
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z')
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Insufficient permissions',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Akses ditolak. Hanya pemilik yang dapat mengakses resource ini.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The tanggal field is required. (and 1 more error)'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'tanggal',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The tanggal field is required.')
                                ),
                            ],
                            type: 'object'
                        )
                    ]
                )
            )
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
            new OA\Response(
                response: 200,
                description: 'Data berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data transaksi keuangan berhasil diambil.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'panen_id', type: 'integer', nullable: true, example: 2),
                                new OA\Property(property: 'jenis', type: 'string', example: 'pemasukan'),
                                new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-11-22'),
                                new OA\Property(property: 'jumlah', type: 'string', example: '1500000.00'),
                                new OA\Property(property: 'persentase_kas', type: 'string', nullable: true, example: '35.00'),
                                new OA\Property(property: 'kas_operasional', type: 'string', nullable: true, example: '525000.00'),
                                new OA\Property(property: 'laba_bersih', type: 'string', nullable: true, example: '975000.00'),
                                new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Penjualan panen November'),
                                new OA\Property(
                                    property: 'user',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'nama', type: 'string', example: 'Pemilik Kebun'),
                                        new OA\Property(property: 'username', type: 'string', example: 'pemilik'),
                                        new OA\Property(property: 'role', type: 'string', example: 'pemilik')
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'panen',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 2),
                                        new OA\Property(property: 'budidaya_id', type: 'integer', example: 3),
                                        new OA\Property(property: 'tanggal_panen', type: 'string', format: 'date', example: '2026-12-10'),
                                        new OA\Property(property: 'berat_kg', type: 'number', nullable: true, example: 12.5),
                                        new OA\Property(property: 'status', type: 'string', example: 'selesai'),
                                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z'),
                                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z')
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z')
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Insufficient permissions',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Akses ditolak. Hanya pemilik yang dapat mengakses resource ini.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Resource not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Data transaksi tidak ditemukan.')
                    ]
                )
            )
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
            new OA\Response(
                response: 200,
                description: 'Transaksi berhasil diperbarui dengan recalculation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Transaksi keuangan berhasil diperbarui.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'panen_id', type: 'integer', nullable: true, example: 2),
                                new OA\Property(property: 'jenis', type: 'string', example: 'pemasukan'),
                                new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-11-22'),
                                new OA\Property(property: 'jumlah', type: 'string', example: '1500000.00'),
                                new OA\Property(property: 'persentase_kas', type: 'string', nullable: true, example: '35.00'),
                                new OA\Property(property: 'kas_operasional', type: 'string', nullable: true, example: '525000.00'),
                                new OA\Property(property: 'laba_bersih', type: 'string', nullable: true, example: '975000.00'),
                                new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Penjualan panen November'),
                                new OA\Property(
                                    property: 'user',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'nama', type: 'string', example: 'Pemilik Kebun'),
                                        new OA\Property(property: 'username', type: 'string', example: 'pemilik'),
                                        new OA\Property(property: 'role', type: 'string', example: 'pemilik')
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'panen',
                                    nullable: true,
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 2),
                                        new OA\Property(property: 'budidaya_id', type: 'integer', example: 3),
                                        new OA\Property(property: 'tanggal_panen', type: 'string', format: 'date', example: '2026-12-10'),
                                        new OA\Property(property: 'berat_kg', type: 'number', nullable: true, example: 12.5),
                                        new OA\Property(property: 'status', type: 'string', example: 'selesai'),
                                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z'),
                                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z')
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z')
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Insufficient permissions',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Akses ditolak. Hanya pemilik yang dapat mengakses resource ini.')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Resource not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Data transaksi tidak ditemukan.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The tanggal field is required. (and 1 more error)'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'tanggal',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The tanggal field is required.')
                                ),
                            ],
                            type: 'object'
                        )
                    ]
                )
            )
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
