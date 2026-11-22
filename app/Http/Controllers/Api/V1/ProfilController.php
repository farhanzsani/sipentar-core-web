<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProfilUpdateRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ProfilController extends Controller
{
    use ApiResponse;

    public function show(): JsonResponse
    {
        return $this->successResponse(
            new UserResource(auth()->user()),
            'Data profil berhasil diambil.'
        );
    }

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
