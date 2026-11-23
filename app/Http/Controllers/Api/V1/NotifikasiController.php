<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Stok;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class NotifikasiController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/v1/notifikasi',
        summary: 'Daftar notifikasi stok rendah',
        security: [['bearerAuth' => []]],
        tags: ['Notifikasi'],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Data notifikasi berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data notifikasi berhasil diambil.'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'nama_bahan', type: 'string', example: 'Nutrisi A'),
                                    new OA\Property(property: 'kategori', type: 'string', example: 'nutrisi'),
                                    new OA\Property(property: 'jumlah_stok', type: 'string', example: '5.00'),
                                    new OA\Property(property: 'batas_minimum', type: 'string', example: '10.00'),
                                    new OA\Property(property: 'message', type: 'string', example: 'Stok Nutrisi A rendah. Sisa: 5.00 liter.')
                                ],
                                type: 'object'
                            )
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
    public function index(): JsonResponse
    {
        $lowStocks = Stok::whereRaw('jumlah_stok <= batas_minimum')->get();

        $notifikasi = $lowStocks->map(function ($stok) {
            return [
                'id' => $stok->id,
                'nama_bahan' => $stok->nama_bahan,
                'kategori' => $stok->kategori,
                'jumlah_stok' => $stok->jumlah_stok,
                'batas_minimum' => $stok->batas_minimum,
                'message' => "Stok {$stok->nama_bahan} rendah. Sisa: {$stok->jumlah_stok} {$stok->satuan}.",
            ];
        });

        return $this->successResponse(
            $notifikasi,
            'Data notifikasi berhasil diambil.'
        );
    }
}
