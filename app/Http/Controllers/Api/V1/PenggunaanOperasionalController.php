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

class PenggunaanOperasionalController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected StokService $stokService
    ) {}

    /**
     * Display a listing of penggunaan operasional with filters
     */
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

    /**
     * Store a newly created penggunaan operasional
     */
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
