<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MejaTanamResource;
use App\Models\MejaTanam;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MejaTanamController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/v1/meja-tanam',
        summary: 'Daftar meja tanam',
        security: [['bearerAuth' => []]],
        tags: ['MejaTanam'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data meja tanam berhasil diambil'),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
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
