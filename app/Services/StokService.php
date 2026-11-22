<?php

namespace App\Services;

use App\Models\Stok;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StokService
{
    /**
     * Reduce stock quantity in a database transaction
     *
     * @param int $stokId
     * @param float|string $jumlah
     * @return Stok
     * @throws ValidationException
     */
    public function reduceStock(int $stokId, float|string $jumlah): Stok
    {
        return DB::transaction(function () use ($stokId, $jumlah) {
            $stok = Stok::findOrFail($stokId);

            // Check if stock is sufficient
            if (bccomp($stok->jumlah_stok, $jumlah, 2) < 0) {
                throw ValidationException::withMessages([
                    'jumlah' => ['Stok tidak mencukupi.'],
                ]);
            }

            // Reduce stock
            $stok->jumlah_stok = bcsub($stok->jumlah_stok, $jumlah, 2);
            $stok->save();

            return $stok;
        });
    }
}
