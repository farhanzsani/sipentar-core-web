<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PanenStoreRequest;
use App\Http\Requests\Api\V1\PanenUpdateRequest;
use App\Http\Resources\Api\V1\PanenResource;
use App\Models\Panen;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PanenController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/v1/panen',
        summary: 'Daftar data panen',
        security: [['bearerAuth' => []]],
        tags: ['Panen'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'budidaya_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'meja_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['terjadwal', 'selesai'])),
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data panen berhasil diambil')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Panen::with(['budidaya.meja', 'budidaya.user']);

        // Filter by budidaya_id
        if ($request->filled('budidaya_id')) {
            $query->where('budidaya_id', $request->budidaya_id);
        }

        // Filter by meja_id (via budidaya relation)
        if ($request->filled('meja_id')) {
            $query->whereHas('budidaya', function ($q) use ($request) {
                $q->where('meja_id', $request->meja_id);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range (from)
        if ($request->filled('from')) {
            $query->whereDate('tanggal_panen', '>=', $request->from);
        }

        // Filter by date range (to)
        if ($request->filled('to')) {
            $query->whereDate('tanggal_panen', '<=', $request->to);
        }

        $perPage = $request->get('per_page', 15);
        $panens = $query->paginate($perPage);

        return $this->successResponse(
            PanenResource::collection($panens)->response()->getData(true),
            'Data panen berhasil diambil.'
        );
    }

    #[OA\Post(
        path: '/api/v1/panen',
        summary: 'Buat jadwal panen baru',
        security: [['bearerAuth' => []]],
        tags: ['Panen'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['budidaya_id', 'tanggal_panen', 'status'],
                properties: [
                    new OA\Property(property: 'budidaya_id', type: 'integer'),
                    new OA\Property(property: 'tanggal_panen', type: 'string', format: 'date'),
                    new OA\Property(property: 'status', type: 'string', enum: ['terjadwal', 'selesai']),
                    new OA\Property(property: 'berat_kg', type: 'number', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Panen berhasil dibuat'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function store(PanenStoreRequest $request): JsonResponse
    {
        $panen = Panen::create($request->validated());

        $panen->load(['budidaya.meja', 'budidaya.user']);

        return $this->successResponse(
            new PanenResource($panen),
            'Panen berhasil dibuat.',
            201
        );
    }

    #[OA\Get(
        path: '/api/v1/panen/{id}',
        summary: 'Detail data panen',
        security: [['bearerAuth' => []]],
        tags: ['Panen'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data panen berhasil diambil'),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $panen = Panen::with(['budidaya.meja', 'budidaya.user'])->findOrFail($id);

        return $this->successResponse(
            new PanenResource($panen),
            'Data panen berhasil diambil.'
        );
    }

    #[OA\Put(
        path: '/api/v1/panen/{id}',
        summary: 'Perbarui data panen',
        security: [['bearerAuth' => []]],
        tags: ['Panen'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'budidaya_id', type: 'integer'),
                    new OA\Property(property: 'tanggal_panen', type: 'string', format: 'date'),
                    new OA\Property(property: 'status', type: 'string', enum: ['terjadwal', 'selesai']),
                    new OA\Property(property: 'berat_kg', type: 'number', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Panen berhasil diperbarui'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function update(PanenUpdateRequest $request, int $id): JsonResponse
    {
        $panen = Panen::findOrFail($id);
        
        $panen->update($request->validated());

        $panen->load(['budidaya.meja', 'budidaya.user']);

        return $this->successResponse(
            new PanenResource($panen),
            'Panen berhasil diperbarui.'
        );
    }
}
