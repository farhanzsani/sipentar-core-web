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
            new OA\Response(response: 200, description: 'Profil berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated')
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
            new OA\Response(response: 200, description: 'Profil berhasil diperbarui'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthenticated')
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
