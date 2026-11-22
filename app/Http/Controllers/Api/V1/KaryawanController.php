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

class KaryawanController extends Controller
{
    use ApiResponse;

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

    public function store(KaryawanStoreRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return $this->successResponse(
            new UserResource($user),
            'Karyawan berhasil ditambahkan.',
            201
        );
    }

    public function show(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        return $this->successResponse(
            new UserResource($user),
            'Data karyawan berhasil diambil.'
        );
    }

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
