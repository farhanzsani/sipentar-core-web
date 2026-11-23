<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StokStoreRequest;
use App\Http\Requests\Api\V1\StokUpdateRequest;
use App\Http\Resources\Api\V1\StokResource;
use App\Models\Stok;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StokController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/v1/stok',
        summary: 'Daftar stok bahan',
        security: [['bearerAuth' => []]],
        tags: ['Stok'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data stok berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data stok berhasil diambil.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'nama_bahan', type: 'string', example: 'Pupuk NPK'),
                                            new OA\Property(property: 'kategori', type: 'string', example: 'Pupuk'),
                                            new OA\Property(property: 'satuan', type: 'string', example: 'kg'),
                                            new OA\Property(property: 'jumlah_stok', type: 'number', example: 25.50),
                                            new OA\Property(property: 'batas_minimum', type: 'number', example: 10.00),
                                            new OA\Property(property: 'is_low', type: 'boolean', example: false),
                                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z'),
                                            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z')
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'links',
                                    properties: [
                                        new OA\Property(property: 'first', type: 'string', example: 'http://localhost:8000/api/v1/stok?page=1'),
                                        new OA\Property(property: 'last', type: 'string', example: 'http://localhost:8000/api/v1/stok?page=3'),
                                        new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
                                        new OA\Property(property: 'next', type: 'string', nullable: true, example: 'http://localhost:8000/api/v1/stok?page=2')
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
        $perPage = $request->get('per_page', 15);
        $stoks = Stok::paginate($perPage);

        return $this->successResponse(
            StokResource::collection($stoks)->response()->getData(true),
            'Data stok berhasil diambil.'
        );
    }

    #[OA\Post(
        path: '/api/v1/stok',
        summary: 'Tambah stok baru',
        security: [['bearerAuth' => []]],
        tags: ['Stok'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama_bahan', 'kategori', 'satuan', 'jumlah_stok', 'batas_minimum'],
                properties: [
                    new OA\Property(property: 'nama_bahan', type: 'string', maxLength: 100),
                    new OA\Property(property: 'kategori', type: 'string', maxLength: 50),
                    new OA\Property(property: 'satuan', type: 'string', maxLength: 20),
                    new OA\Property(property: 'jumlah_stok', type: 'number', minimum: 0),
                    new OA\Property(property: 'batas_minimum', type: 'number', minimum: 0)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Stok berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Stok berhasil dibuat.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'nama_bahan', type: 'string', example: 'Pupuk NPK'),
                                new OA\Property(property: 'kategori', type: 'string', example: 'Pupuk'),
                                new OA\Property(property: 'satuan', type: 'string', example: 'kg'),
                                new OA\Property(property: 'jumlah_stok', type: 'number', example: 25.50),
                                new OA\Property(property: 'batas_minimum', type: 'number', example: 10.00),
                                new OA\Property(property: 'is_low', type: 'boolean', example: false),
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
                        new OA\Property(property: 'message', type: 'string', example: 'The nama_bahan field is required. (and 1 more error)'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'nama_bahan',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The nama_bahan field is required.')
                                ),
                            ],
                            type: 'object'
                        )
                    ]
                )
            )
        ]
    )]
    public function store(StokStoreRequest $request): JsonResponse
    {
        $stok = Stok::create($request->validated());

        return $this->successResponse(
            new StokResource($stok),
            'Stok berhasil dibuat.',
            201
        );
    }

    #[OA\Get(
        path: '/api/v1/stok/{id}',
        summary: 'Detail stok',
        security: [['bearerAuth' => []]],
        tags: ['Stok'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data stok berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data stok berhasil diambil.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'nama_bahan', type: 'string', example: 'Pupuk NPK'),
                                new OA\Property(property: 'kategori', type: 'string', example: 'Pupuk'),
                                new OA\Property(property: 'satuan', type: 'string', example: 'kg'),
                                new OA\Property(property: 'jumlah_stok', type: 'number', example: 25.50),
                                new OA\Property(property: 'batas_minimum', type: 'number', example: 10.00),
                                new OA\Property(property: 'is_low', type: 'boolean', example: false),
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
                        new OA\Property(property: 'message', type: 'string', example: 'Data stok tidak ditemukan.')
                    ]
                )
            )
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $stok = Stok::findOrFail($id);

        return $this->successResponse(
            new StokResource($stok),
            'Data stok berhasil diambil.'
        );
    }

    #[OA\Put(
        path: '/api/v1/stok/{id}',
        summary: 'Perbarui stok',
        security: [['bearerAuth' => []]],
        tags: ['Stok'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nama_bahan', type: 'string'),
                    new OA\Property(property: 'kategori', type: 'string'),
                    new OA\Property(property: 'satuan', type: 'string'),
                    new OA\Property(property: 'jumlah_stok', type: 'number'),
                    new OA\Property(property: 'batas_minimum', type: 'number')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Stok berhasil diperbarui',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Stok berhasil diperbarui.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'nama_bahan', type: 'string', example: 'Pupuk NPK'),
                                new OA\Property(property: 'kategori', type: 'string', example: 'Pupuk'),
                                new OA\Property(property: 'satuan', type: 'string', example: 'kg'),
                                new OA\Property(property: 'jumlah_stok', type: 'number', example: 25.50),
                                new OA\Property(property: 'batas_minimum', type: 'number', example: 10.00),
                                new OA\Property(property: 'is_low', type: 'boolean', example: false),
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
                        new OA\Property(property: 'message', type: 'string', example: 'Data stok tidak ditemukan.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The nama_bahan field is required. (and 1 more error)'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'nama_bahan',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The nama_bahan field is required.')
                                ),
                            ],
                            type: 'object'
                        )
                    ]
                )
            )
        ]
    )]
    public function update(StokUpdateRequest $request, int $id): JsonResponse
    {
        $stok = Stok::findOrFail($id);
        $stok->update($request->validated());

        return $this->successResponse(
            new StokResource($stok),
            'Stok berhasil diperbarui.'
        );
    }
}
