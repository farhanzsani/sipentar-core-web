<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PenggunaanOperasionalStoreRequest;
use App\Http\Resources\Api\V1\PenggunaanOperasionalResource;
use App\Models\PenggunaanOperasional;
use App\Services\StokService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PenggunaanOperasionalController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected StokService $stokService
    ) {}

    #[OA\Get(
        path: '/api/v1/penggunaan-operasional',
        summary: 'Daftar penggunaan operasional',
        security: [['bearerAuth' => []]],
        tags: ['PenggunaanOperasional'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'stok_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data penggunaan operasional berhasil diambil')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = PenggunaanOperasional::with(['user', 'stok']);

        // Filter by stok_id
        if ($request->filled('stok_id')) {
            $query->where('stok_id', $request->stok_id);
        }

        // Filter by date range (from)
        if ($request->filled('from')) {
            $query->whereDate('tanggal', '>=', $request->from);
        }

        // Filter by date range (to)
        if ($request->filled('to')) {
            $query->whereDate('tanggal', '<=', $request->to);
        }

        $perPage = $request->get('per_page', 15);
        $penggunaanOperasionals = $query->paginate($perPage);

        return $this->successResponse(
            PenggunaanOperasionalResource::collection($penggunaanOperasionals)->response()->getData(true),
            'Data penggunaan operasional berhasil diambil.'
        );
    }

    #[OA\Post(
        path: '/api/v1/penggunaan-operasional',
        summary: 'Catat penggunaan operasional baru',
        security: [['bearerAuth' => []]],
        tags: ['PenggunaanOperasional'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['stok_id', 'tanggal', 'jumlah'],
                properties: [
                    new OA\Property(property: 'stok_id', type: 'integer'),
                    new OA\Property(property: 'tanggal', type: 'string', format: 'date'),
                    new OA\Property(property: 'jumlah', type: 'number', minimum: 0.01),
                    new OA\Property(property: 'keterangan', type: 'string', nullable: true, maxLength: 255)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Penggunaan operasional berhasil dicatat'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function store(PenggunaanOperasionalStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Reduce stock in transaction
        $this->stokService->reduceStock($validated['stok_id'], $validated['jumlah']);

        // Create penggunaan operasional record
        $penggunaanOperasional = PenggunaanOperasional::create($validated);
        $penggunaanOperasional->load(['user', 'stok']);

        return $this->successResponse(
            new PenggunaanOperasionalResource($penggunaanOperasional),
            'Penggunaan operasional berhasil dicatat.',
            201
        );
    }
}
