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

class PanenController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of panen with filters
     */
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

    /**
     * Store a newly created panen
     */
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

    /**
     * Display the specified panen
     */
    public function show(int $id): JsonResponse
    {
        $panen = Panen::with(['budidaya.meja', 'budidaya.user'])->findOrFail($id);

        return $this->successResponse(
            new PanenResource($panen),
            'Data panen berhasil diambil.'
        );
    }

    /**
     * Update the specified panen
     */
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
