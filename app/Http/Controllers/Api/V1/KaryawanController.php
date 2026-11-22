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
            new OA\Response(response: 200, description: 'Daftar karyawan berhasil diambil'),
            new OA\Response(response: 403, description: 'Forbidden')
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
            new OA\Response(response: 201, description: 'Karyawan berhasil ditambahkan'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 403, description: 'Forbidden')
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
            new OA\Response(response: 200, description: 'Data karyawan berhasil diambil'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 403, description: 'Forbidden')
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
            new OA\Response(response: 200, description: 'Karyawan berhasil diperbarui'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 403, description: 'Forbidden')
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
