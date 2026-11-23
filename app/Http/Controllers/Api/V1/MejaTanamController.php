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
            new OA\Response(
                response: 200,
                description: 'Data meja tanam berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data meja tanam berhasil diambil.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'nomor_meja', type: 'integer', example: 1),
                                            new OA\Property(property: 'kapasitas_lubang', type: 'integer', example: 60),
                                            new OA\Property(property: 'status', type: 'string', example: 'tersedia'),
                                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z'),
                                            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-11-22T10:30:00.000000Z')
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'links',
                                    properties: [
                                        new OA\Property(property: 'first', type: 'string', example: 'http://localhost:8000/api/v1/meja-tanam?page=1'),
                                        new OA\Property(property: 'last', type: 'string', example: 'http://localhost:8000/api/v1/meja-tanam?page=3'),
                                        new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
                                        new OA\Property(property: 'next', type: 'string', nullable: true, example: 'http://localhost:8000/api/v1/meja-tanam?page=2')
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'meta',
                                    properties: [
                                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                        new OA\Property(property: 'last_page', type: 'integer', example: 3),
                                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                        new OA\Property(property: 'total', type: 'integer', example: 42)
                                    ],
                                    type: 'object'
                                )
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
                    ]
                )
            )
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
