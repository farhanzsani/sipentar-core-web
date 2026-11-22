<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Stok;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class NotifikasiController extends Controller
{
    use ApiResponse;

    /**
     * Get list of low stock notifications
     */
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
