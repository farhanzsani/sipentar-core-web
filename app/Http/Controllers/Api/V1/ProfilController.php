<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProfilUpdateRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class ProfilController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/v1/profil',
        summary: 'Lihat profil pengguna yang sedang login',
        security: [['bearerAuth' => []]],
        tags: ['Profil'],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Profil berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data profil berhasil diambil.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'nama', type: 'string', example: 'John Doe'),
                                new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                                new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                                new OA\Property(property: 'role', type: 'string', example: 'pemilik'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-11-22T10:00:00.000000Z'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-11-22T10:00:00.000000Z')
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
    public function show(): JsonResponse
    {
        return $this->successResponse(
            new UserResource(auth()->user()),
            'Data profil berhasil diambil.'
        );
    }

    #[OA\Put(
        path: '/api/v1/profil',
        summary: 'Perbarui profil pengguna',
        security: [['bearerAuth' => []]],
        tags: ['Profil'],
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
                description: 'Profil berhasil diperbarui',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Profil berhasil diperbarui.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'nama', type: 'string', example: 'John Doe Updated'),
                                new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                                new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                                new OA\Property(property: 'role', type: 'string', example: 'pemilik'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time')
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
    public function update(ProfilUpdateRequest $request): JsonResponse
    {
        $user = auth()->user();

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
            'Profil berhasil diperbarui.'
        );
    }
}
