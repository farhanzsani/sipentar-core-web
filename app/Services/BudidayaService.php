<?php

namespace App\Services;

use App\Models\Budidaya;
use App\Models\MejaTanam;

class BudidayaService
{
    /**
     * Update meja status based on whether it has any running budidaya
     * 
     * @param int $mejaId
     * @return void
     */
    public function updateMejaStatus(int $mejaId): void
    {
        $meja = MejaTanam::findOrFail($mejaId);
        
        // Check if meja has any budidaya with status 'berjalan'
        $hasBerjalanBudidaya = $meja->budidayas()
            ->where('status', 'berjalan')
            ->exists();
        
        // Update meja status accordingly
        $meja->status = $hasBerjalanBudidaya ? 'terisi' : 'kosong';
        $meja->save();
    }
}
