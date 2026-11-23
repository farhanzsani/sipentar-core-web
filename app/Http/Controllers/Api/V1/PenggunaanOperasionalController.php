<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PenggunaanOperasionalStoreRequest;
use App\Http\Resources\Api\V1\PenggunaanOperasionalResource;
use App\Models\PenggunaanOperasional;
use App\Services\StokService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PenggunaanOperasionalController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected StokService $stokService
    ) {}

    #[OA\Get(
        path: '/api/v1/penggunaan-operasional',
        summary: 'Daftar penggunaan operasional',
        security: [['bearerAuth' => []]],
        tags: ['PenggunaanOperasional'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'stok_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data penggunaan operasional berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data penggunaan operasional berhasil diambil.'),
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
                                            new OA\Property(property: 'stok_id', type: 'integer', example: 2),
                                            new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-11-22'),
                                            new OA\Property(property: 'jumlah', type: 'number', example: 5.00),
                                            new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Penyiraman rutin'),
                                            new OA\Property(
                                                property: 'user',
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                                    new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso'),
                                                    new OA\Property(property: 'username', type: 'string', example: 'budi_santoso'),
                                                    new OA\Property(property: 'role', type: 'string', example: 'pegawai')
                                                ],
                                                type: 'object'
                                            ),
                                            new OA\Property(
                                                property: 'stok',
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'integer', example: 2),
                                                    new OA\Property(property: 'nama_bahan', type: 'string', example: 'Pupuk NPK'),
                                                    new OA\Property(property: 'kategori', type: 'string', example: 'Pupuk'),
                                                    new OA\Property(property: 'satuan', type: 'string', example: 'kg'),
                                                    new OA\Property(property: 'jumlah_stok', type: 'number', example: 20.50),
                                                    new OA\Property(property: 'batas_minimum', type: 'number', example: 10.00),
                                                    new OA\Property(property: 'is_low', type: 'boolean', example: false),
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
                                        new OA\Property(property: 'first', type: 'string', example: 'http://localhost:8000/api/v1/penggunaan-operasional?page=1'),
                                        new OA\Property(property: 'last', type: 'string', example: 'http://localhost:8000/api/v1/penggunaan-operasional?page=3'),
                                        new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
                                        new OA\Property(property: 'next', type: 'string', nullable: true, example: 'http://localhost:8000/api/v1/penggunaan-operasional?page=2')
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
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = PenggunaanOperasional::with(['user', 'stok']);

        // Filter by stok_id
        if ($request->filled('stok_id')) {
            $query->where('stok_id', $request->stok_id);
        }

        // Filter by date range (from)
        if ($request->filled('from')) {
            $query->whereDate('tanggal', '>=', $request->from);
        }

        // Filter by date range (to)
        if ($request->filled('to')) {
            $query->whereDate('tanggal', '<=', $request->to);
        }

        $perPage = $request->get('per_page', 15);
        $penggunaanOperasionals = $query->paginate($perPage);

        return $this->successResponse(
            PenggunaanOperasionalResource::collection($penggunaanOperasionals)->response()->getData(true),
            'Data penggunaan operasional berhasil diambil.'
        );
    }

    #[OA\Post(
        path: '/api/v1/penggunaan-operasional',
        summary: 'Catat penggunaan operasional baru',
        security: [['bearerAuth' => []]],
        tags: ['PenggunaanOperasional'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['stok_id', 'tanggal', 'jumlah'],
                properties: [
                    new OA\Property(property: 'stok_id', type: 'integer'),
                    new OA\Property(property: 'tanggal', type: 'string', format: 'date'),
                    new OA\Property(property: 'jumlah', type: 'number', minimum: 0.01),
                    new OA\Property(property: 'keterangan', type: 'string', nullable: true, maxLength: 255)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Penggunaan operasional berhasil dicatat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Penggunaan operasional berhasil dicatat.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'stok_id', type: 'integer', example: 2),
                                new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-11-22'),
                                new OA\Property(property: 'jumlah', type: 'number', example: 5.00),
                                new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Penyiraman rutin'),
                                new OA\Property(
                                    property: 'user',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso'),
                                        new OA\Property(property: 'username', type: 'string', example: 'budi_santoso'),
                                        new OA\Property(property: 'role', type: 'string', example: 'pegawai')
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'stok',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 2),
                                        new OA\Property(property: 'nama_bahan', type: 'string', example: 'Pupuk NPK'),
                                        new OA\Property(property: 'kategori', type: 'string', example: 'Pupuk'),
                                        new OA\Property(property: 'satuan', type: 'string', example: 'kg'),
                                        new OA\Property(property: 'jumlah_stok', type: 'number', example: 20.50),
                                        new OA\Property(property: 'batas_minimum', type: 'number', example: 10.00),
                                        new OA\Property(property: 'is_low', type: 'boolean', example: false),
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
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The stok_id field is required. (and 1 more error)'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'stok_id',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The stok_id field is required.')
                                ),
                            ],
                            type: 'object'
                        )
                    ]
                )
            )
        ]
    )]
    public function store(PenggunaanOperasionalStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Reduce stock in transaction
        $this->stokService->reduceStock($validated['stok_id'], $validated['jumlah']);

        // Create penggunaan operasional record
        $penggunaanOperasional = PenggunaanOperasional::create($validated);
        $penggunaanOperasional->load(['user', 'stok']);

        return $this->successResponse(
            new PenggunaanOperasionalResource($penggunaanOperasional),
            'Penggunaan operasional berhasil dicatat.',
            201
        );
    }
}
