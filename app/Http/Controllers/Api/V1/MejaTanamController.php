<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MejaTanamResource;
use App\Models\MejaTanam;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MejaTanamController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of meja tanam
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $mejaTanams = MejaTanam::paginate($perPage);

        return $this->successResponse(
            MejaTanamResource::collection($mejaTanams)->response()->getData(true),
            'Data meja tanam berhasil diambil.'
        );
    }
}
