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
            new OA\Response(response: 200, description: 'Data stok berhasil diambil')
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
            new OA\Response(response: 201, description: 'Stok berhasil dibuat'),
            new OA\Response(response: 422, description: 'Validation error')
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
            new OA\Response(response: 200, description: 'Data stok berhasil diambil'),
            new OA\Response(response: 404, description: 'Not found')
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
            new OA\Response(response: 200, description: 'Stok berhasil diperbarui'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 404, description: 'Not found')
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
