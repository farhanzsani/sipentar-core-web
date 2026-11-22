<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BudidayaStoreRequest;
use App\Http\Requests\Api\V1\BudidayaUpdateRequest;
use App\Http\Resources\Api\V1\BudidayaResource;
use App\Models\Budidaya;
use App\Services\BudidayaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BudidayaController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected BudidayaService $budidayaService
    ) {}

    #[OA\Get(
        path: '/api/v1/budidaya',
        summary: 'Daftar budidaya',
        security: [['bearerAuth' => []]],
        tags: ['Budidaya'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'meja_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['berjalan', 'selesai'])),
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data budidaya berhasil diambil')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Budidaya::with(['user', 'meja']);

        // Filter by meja_id
        if ($request->filled('meja_id')) {
            $query->where('meja_id', $request->meja_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range (from)
        if ($request->filled('from')) {
            $query->whereDate('tanggal_semai', '>=', $request->from);
        }

        // Filter by date range (to)
        if ($request->filled('to')) {
            $query->whereDate('tanggal_semai', '<=', $request->to);
        }

        $perPage = $request->get('per_page', 15);
        $budidayas = $query->paginate($perPage);

        return $this->successResponse(
            BudidayaResource::collection($budidayas)->response()->getData(true),
            'Data budidaya berhasil diambil.'
        );
    }

    #[OA\Post(
        path: '/api/v1/budidaya',
        summary: 'Buat budidaya baru',
        security: [['bearerAuth' => []]],
        tags: ['Budidaya'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['meja_id', 'tanggal_semai', 'jumlah_tanaman', 'status'],
                properties: [
                    new OA\Property(property: 'meja_id', type: 'integer'),
                    new OA\Property(property: 'tanggal_semai', type: 'string', format: 'date'),
                    new OA\Property(property: 'jumlah_tanaman', type: 'integer', minimum: 1),
                    new OA\Property(property: 'status', type: 'string', enum: ['berjalan', 'selesai'])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Budidaya berhasil dibuat'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function store(BudidayaStoreRequest $request): JsonResponse
    {
        $budidaya = Budidaya::create($request->validated());

        // Update meja status after creating budidaya
        $this->budidayaService->updateMejaStatus($budidaya->meja_id);

        $budidaya->load(['user', 'meja']);

        return $this->successResponse(
            new BudidayaResource($budidaya),
            'Budidaya berhasil dibuat.',
            201
        );
    }

    #[OA\Get(
        path: '/api/v1/budidaya/{id}',
        summary: 'Detail budidaya',
        security: [['bearerAuth' => []]],
        tags: ['Budidaya'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data budidaya berhasil diambil'),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $budidaya = Budidaya::with(['user', 'meja'])->findOrFail($id);

        return $this->successResponse(
            new BudidayaResource($budidaya),
            'Data budidaya berhasil diambil.'
        );
    }

    #[OA\Put(
        path: '/api/v1/budidaya/{id}',
        summary: 'Perbarui budidaya',
        security: [['bearerAuth' => []]],
        tags: ['Budidaya'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'meja_id', type: 'integer'),
                    new OA\Property(property: 'tanggal_semai', type: 'string', format: 'date'),
                    new OA\Property(property: 'jumlah_tanaman', type: 'integer'),
                    new OA\Property(property: 'status', type: 'string', enum: ['berjalan', 'selesai'])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Budidaya berhasil diperbarui'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function update(BudidayaUpdateRequest $request, int $id): JsonResponse
    {
        $budidaya = Budidaya::findOrFail($id);
        
        $oldMejaId = $budidaya->meja_id;
        
        $budidaya->update($request->validated());

        // Update meja status for both old and new meja (if changed)
        $this->budidayaService->updateMejaStatus($budidaya->meja_id);
        
        if ($oldMejaId !== $budidaya->meja_id) {
            $this->budidayaService->updateMejaStatus($oldMejaId);
        }

        $budidaya->load(['user', 'meja']);

        return $this->successResponse(
            new BudidayaResource($budidaya),
            'Budidaya berhasil diperbarui.'
        );
    }
}
