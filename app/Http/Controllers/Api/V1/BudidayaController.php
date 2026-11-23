<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BudidayaStoreRequest;
use App\Http\Requests\Api\V1\BudidayaUpdateRequest;
use App\Http\Resources\Api\V1\BudidayaResource;
use App\Models\Budidaya;
use App\Services\BudidayaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BudidayaController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected BudidayaService $budidayaService
    ) {}

    #[OA\Get(
        path: '/api/v1/budidaya',
        summary: 'Daftar budidaya',
        security: [['bearerAuth' => []]],
        tags: ['Budidaya'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'meja_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['berjalan', 'selesai'])),
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data budidaya berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data budidaya berhasil diambil.'),
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
                                            new OA\Property(property: 'meja_id', type: 'integer', example: 2),
                                            new OA\Property(property: 'tanggal_semai', type: 'string', format: 'date', example: '2026-11-01'),
                                            new OA\Property(property: 'jumlah_tanaman', type: 'integer', example: 50),
                                            new OA\Property(property: 'status', type: 'string', example: 'berjalan'),
                                            new OA\Property(property: 'hss', type: 'integer', example: 14),
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
                                                property: 'meja',
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'integer', example: 2),
                                                    new OA\Property(property: 'nomor_meja', type: 'integer', example: 2),
                                                    new OA\Property(property: 'kapasitas_lubang', type: 'integer', example: 60),
                                                    new OA\Property(property: 'status', type: 'string', example: 'digunakan'),
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
                                        new OA\Property(property: 'first', type: 'string', example: 'http://localhost:8000/api/v1/budidaya?page=1'),
                                        new OA\Property(property: 'last', type: 'string', example: 'http://localhost:8000/api/v1/budidaya?page=3'),
                                        new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
                                        new OA\Property(property: 'next', type: 'string', nullable: true, example: 'http://localhost:8000/api/v1/budidaya?page=2')
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
        $query = Budidaya::with(['user', 'meja']);

        // Filter by meja_id
        if ($request->filled('meja_id')) {
            $query->where('meja_id', $request->meja_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range (from)
        if ($request->filled('from')) {
            $query->whereDate('tanggal_semai', '>=', $request->from);
        }

        // Filter by date range (to)
        if ($request->filled('to')) {
            $query->whereDate('tanggal_semai', '<=', $request->to);
        }

        $perPage = $request->get('per_page', 15);
        $budidayas = $query->paginate($perPage);

        return $this->successResponse(
            BudidayaResource::collection($budidayas)->response()->getData(true),
            'Data budidaya berhasil diambil.'
        );
    }

    #[OA\Post(
        path: '/api/v1/budidaya',
        summary: 'Buat budidaya baru',
        security: [['bearerAuth' => []]],
        tags: ['Budidaya'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['meja_id', 'tanggal_semai', 'jumlah_tanaman', 'status'],
                properties: [
                    new OA\Property(property: 'meja_id', type: 'integer'),
                    new OA\Property(property: 'tanggal_semai', type: 'string', format: 'date'),
                    new OA\Property(property: 'jumlah_tanaman', type: 'integer', minimum: 1),
                    new OA\Property(property: 'status', type: 'string', enum: ['berjalan', 'selesai'])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Budidaya berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Budidaya berhasil dibuat.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'meja_id', type: 'integer', example: 2),
                                new OA\Property(property: 'tanggal_semai', type: 'string', format: 'date', example: '2026-11-01'),
                                new OA\Property(property: 'jumlah_tanaman', type: 'integer', example: 50),
                                new OA\Property(property: 'status', type: 'string', example: 'berjalan'),
                                new OA\Property(property: 'hss', type: 'integer', example: 14),
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
                                    property: 'meja',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 2),
                                        new OA\Property(property: 'nomor_meja', type: 'integer', example: 2),
                                        new OA\Property(property: 'kapasitas_lubang', type: 'integer', example: 60),
                                        new OA\Property(property: 'status', type: 'string', example: 'digunakan'),
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
                        new OA\Property(property: 'message', type: 'string', example: 'The meja_id field is required. (and 1 more error)'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'meja_id',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The meja_id field is required.')
                                ),
                            ],
                            type: 'object'
                        )
                    ]
                )
            )
        ]
    )]
    public function store(BudidayaStoreRequest $request): JsonResponse
    {
        $budidaya = Budidaya::create($request->validated());

        // Update meja status after creating budidaya
        $this->budidayaService->updateMejaStatus($budidaya->meja_id);

        $budidaya->load(['user', 'meja']);

        return $this->successResponse(
            new BudidayaResource($budidaya),
            'Budidaya berhasil dibuat.',
            201
        );
    }

    #[OA\Get(
        path: '/api/v1/budidaya/{id}',
        summary: 'Detail budidaya',
        security: [['bearerAuth' => []]],
        tags: ['Budidaya'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data budidaya berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data budidaya berhasil diambil.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'meja_id', type: 'integer', example: 2),
                                new OA\Property(property: 'tanggal_semai', type: 'string', format: 'date', example: '2026-11-01'),
                                new OA\Property(property: 'jumlah_tanaman', type: 'integer', example: 50),
                                new OA\Property(property: 'status', type: 'string', example: 'berjalan'),
                                new OA\Property(property: 'hss', type: 'integer', example: 14),
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
                                    property: 'meja',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 2),
                                        new OA\Property(property: 'nomor_meja', type: 'integer', example: 2),
                                        new OA\Property(property: 'kapasitas_lubang', type: 'integer', example: 60),
                                        new OA\Property(property: 'status', type: 'string', example: 'digunakan'),
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
                response: 404,
                description: 'Resource not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Data budidaya tidak ditemukan.')
                    ]
                )
            )
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $budidaya = Budidaya::with(['user', 'meja'])->findOrFail($id);

        return $this->successResponse(
            new BudidayaResource($budidaya),
            'Data budidaya berhasil diambil.'
        );
    }

    #[OA\Put(
        path: '/api/v1/budidaya/{id}',
        summary: 'Perbarui budidaya',
        security: [['bearerAuth' => []]],
        tags: ['Budidaya'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'meja_id', type: 'integer'),
                    new OA\Property(property: 'tanggal_semai', type: 'string', format: 'date'),
                    new OA\Property(property: 'jumlah_tanaman', type: 'integer'),
                    new OA\Property(property: 'status', type: 'string', enum: ['berjalan', 'selesai'])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Budidaya berhasil diperbarui',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Budidaya berhasil diperbarui.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'meja_id', type: 'integer', example: 2),
                                new OA\Property(property: 'tanggal_semai', type: 'string', format: 'date', example: '2026-11-01'),
                                new OA\Property(property: 'jumlah_tanaman', type: 'integer', example: 50),
                                new OA\Property(property: 'status', type: 'string', example: 'berjalan'),
                                new OA\Property(property: 'hss', type: 'integer', example: 14),
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
                                    property: 'meja',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 2),
                                        new OA\Property(property: 'nomor_meja', type: 'integer', example: 2),
                                        new OA\Property(property: 'kapasitas_lubang', type: 'integer', example: 60),
                                        new OA\Property(property: 'status', type: 'string', example: 'digunakan'),
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
                response: 404,
                description: 'Resource not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Data budidaya tidak ditemukan.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The meja_id field is required. (and 1 more error)'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'meja_id',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The meja_id field is required.')
                                ),
                            ],
                            type: 'object'
                        )
                    ]
                )
            )
        ]
    )]
    public function update(BudidayaUpdateRequest $request, int $id): JsonResponse
    {
        $budidaya = Budidaya::findOrFail($id);
        
        $oldMejaId = $budidaya->meja_id;
        
        $budidaya->update($request->validated());

        // Update meja status for both old and new meja (if changed)
        $this->budidayaService->updateMejaStatus($budidaya->meja_id);
        
        if ($oldMejaId !== $budidaya->meja_id) {
            $this->budidayaService->updateMejaStatus($oldMejaId);
        }

        $budidaya->load(['user', 'meja']);

        return $this->successResponse(
            new BudidayaResource($budidaya),
            'Budidaya berhasil diperbarui.'
        );
    }
}
