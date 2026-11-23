<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\KaryawanStoreRequest;
use App\Http\Requests\Api\V1\KaryawanUpdateRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class KaryawanController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/v1/karyawan',
        summary: 'Daftar karyawan (pemilik only)',
        security: [['bearerAuth' => []]],
        tags: ['Karyawan'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar karyawan berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data karyawan berhasil diambil.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso'),
                                            new OA\Property(property: 'username', type: 'string', example: 'budi_santoso'),
                                            new OA\Property(property: 'role', type: 'string', example: 'pegawai')
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'links',
                                    properties: [
                                        new OA\Property(property: 'first', type: 'string', example: 'http://localhost:8000/api/v1/karyawan?page=1'),
                                        new OA\Property(property: 'last', type: 'string', example: 'http://localhost:8000/api/v1/karyawan?page=3'),
                                        new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
                                        new OA\Property(property: 'next', type: 'string', nullable: true, example: 'http://localhost:8000/api/v1/karyawan?page=2')
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
    public function index(): JsonResponse
    {
        $perPage = request('per_page', 15);
        
        $karyawan = User::where('role', UserRole::PEGAWAI)
            ->paginate($perPage);

        return $this->successResponse(
            UserResource::collection($karyawan)->response()->getData(true),
            'Data karyawan berhasil diambil.'
        );
    }

    #[OA\Post(
        path: '/api/v1/karyawan',
        summary: 'Tambah karyawan baru (pemilik only)',
        security: [['bearerAuth' => []]],
        tags: ['Karyawan'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama', 'username', 'password'],
                properties: [
                    new OA\Property(property: 'nama', type: 'string'),
                    new OA\Property(property: 'username', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Karyawan berhasil ditambahkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Karyawan berhasil ditambahkan.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 5),
                                new OA\Property(property: 'nama', type: 'string', example: 'Siti Rahayu'),
                                new OA\Property(property: 'username', type: 'string', example: 'siti_rahayu'),
                                new OA\Property(property: 'role', type: 'string', example: 'pegawai')
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
                        new OA\Property(property: 'message', type: 'string', example: 'The nama field is required. (and 1 more error)'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'nama',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The nama field is required.')
                                ),
                            ],
                            type: 'object'
                        )
                    ]
                )
            )
        ]
    )]
    public function store(KaryawanStoreRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return $this->successResponse(
            new UserResource($user),
            'Karyawan berhasil ditambahkan.',
            201
        );
    }

    #[OA\Get(
        path: '/api/v1/karyawan/{id}',
        summary: 'Detail karyawan (pemilik only)',
        security: [['bearerAuth' => []]],
        tags: ['Karyawan'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data karyawan berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data karyawan berhasil diambil.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso'),
                                new OA\Property(property: 'username', type: 'string', example: 'budi_santoso'),
                                new OA\Property(property: 'role', type: 'string', example: 'pegawai')
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
                        new OA\Property(property: 'message', type: 'string', example: 'Data karyawan tidak ditemukan.')
                    ]
                )
            )
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        return $this->successResponse(
            new UserResource($user),
            'Data karyawan berhasil diambil.'
        );
    }

    #[OA\Put(
        path: '/api/v1/karyawan/{id}',
        summary: 'Perbarui data karyawan (pemilik only)',
        security: [['bearerAuth' => []]],
        tags: ['Karyawan'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama', 'username'],
                properties: [
                    new OA\Property(property: 'nama', type: 'string'),
                    new OA\Property(property: 'username', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', nullable: true, minLength: 8)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Karyawan berhasil diperbarui',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Karyawan berhasil diperbarui.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'nama', type: 'string', example: 'Budi Santoso'),
                                new OA\Property(property: 'username', type: 'string', example: 'budi_santoso'),
                                new OA\Property(property: 'role', type: 'string', example: 'pegawai')
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
                        new OA\Property(property: 'message', type: 'string', example: 'Data karyawan tidak ditemukan.')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The nama field is required. (and 1 more error)'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'nama',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The nama field is required.')
                                ),
                            ],
                            type: 'object'
                        )
                    ]
                )
            )
        ]
    )]
    public function update(KaryawanUpdateRequest $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $data = [
            'nama' => $request->nama,
            'username' => $request->username,
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        return $this->successResponse(
            new UserResource($user->fresh()),
            'Karyawan berhasil diperbarui.'
        );
    }
}
