<?php

namespace Database\Seeders;

use App\Models\MejaTanam;
use Illuminate\Database\Seeder;

class MejaTanamSeeder extends Seeder
{
    public function run(): void
    {
        $mejas = [
            ['nomor_meja' => 1, 'kapasitas_lubang' => 400, 'status' => 'kosong'],
            ['nomor_meja' => 2, 'kapasitas_lubang' => 420, 'status' => 'kosong'],
            ['nomor_meja' => 3, 'kapasitas_lubang' => 410, 'status' => 'kosong'],
            ['nomor_meja' => 4, 'kapasitas_lubang' => 425, 'status' => 'kosong'],
            ['nomor_meja' => 5, 'kapasitas_lubang' => 415, 'status' => 'kosong'],
            ['nomor_meja' => 6, 'kapasitas_lubang' => 430, 'status' => 'kosong'],
        ];

        foreach ($mejas as $meja) {
            MejaTanam::updateOrCreate(
                ['nomor_meja' => $meja['nomor_meja']],
                $meja
            );
        }
    }
}
