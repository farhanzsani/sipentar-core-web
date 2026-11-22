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

class BudidayaController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected BudidayaService $budidayaService
    ) {}

    /**
     * Display a listing of budidaya with filters
     */
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

    /**
     * Store a newly created budidaya
     */
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

    /**
     * Display the specified budidaya
     */
    public function show(int $id): JsonResponse
    {
        $budidaya = Budidaya::with(['user', 'meja'])->findOrFail($id);

        return $this->successResponse(
            new BudidayaResource($budidaya),
            'Data budidaya berhasil diambil.'
        );
    }

    /**
     * Update the specified budidaya
     */
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
