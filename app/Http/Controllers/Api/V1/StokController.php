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

class StokController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of stok
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $stoks = Stok::paginate($perPage);

        return $this->successResponse(
            StokResource::collection($stoks)->response()->getData(true),
            'Data stok berhasil diambil.'
        );
    }

    /**
     * Store a newly created stok
     */
    public function store(StokStoreRequest $request): JsonResponse
    {
        $stok = Stok::create($request->validated());

        return $this->successResponse(
            new StokResource($stok),
            'Stok berhasil dibuat.',
            201
        );
    }

    /**
     * Display the specified stok
     */
    public function show(int $id): JsonResponse
    {
        $stok = Stok::findOrFail($id);

        return $this->successResponse(
            new StokResource($stok),
            'Data stok berhasil diambil.'
        );
    }

    /**
     * Update the specified stok
     */
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
