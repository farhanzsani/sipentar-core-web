<?php

namespace App\Services;

use App\Models\MejaTanam;
use App\Models\Panen;
use App\Models\TransaksiKeuangan;
use Illuminate\Support\Facades\DB;

class KeuanganService
{
    /**
     * Calculate kas operasional from jumlah and persentase
     *
     * @param string|float $jumlah
     * @param string|float $persentaseKas
     * @return string
     */
    public function calculateKasOperasional(string|float $jumlah, string|float $persentaseKas): string
    {
        // kas_operasional = jumlah × persentase_kas / 100
        $result = bcmul((string) $jumlah, (string) $persentaseKas, 4);
        return bcdiv($result, '100', 2);
    }

    /**
     * Calculate laba bersih from jumlah and kas operasional
     *
     * @param string|float $jumlah
     * @param string|float $kasOperasional
     * @return string
     */
    public function calculateLabaBersih(string|float $jumlah, string|float $kasOperasional): string
    {
        // laba_bersih = jumlah - kas_operasional
        return bcsub((string) $jumlah, (string) $kasOperasional, 2);
    }

    /**
     * Calculate HPP (Harga Pokok Produksi)
     *
     * @param int|null $lubang
     * @param int|null $panenId
     * @return array{lubang: int, hpp_per_lubang: string, total_hpp: string}
     */
    public function calculateHpp(?int $lubang = null, ?int $panenId = null): array
    {
        $hppPerLubang = (string) env('HPP_PER_HOLE', 1200);

        // If panen_id provided, get lubang from budidaya.jumlah_tanaman
        if ($panenId) {
            $panen = Panen::with('budidaya')->findOrFail($panenId);
            $lubang = $panen->budidaya->jumlah_tanaman;
        }

        // If no lubang and no panen_id, use sum of all meja kapasitas
        if (!$lubang) {
            $lubang = (int) MejaTanam::sum('kapasitas_lubang');
        }

        $totalHpp = bcmul((string) $lubang, $hppPerLubang, 2);

        return [
            'lubang' => $lubang,
            'hpp_per_lubang' => $hppPerLubang,
            'total_hpp' => $totalHpp,
        ];
    }

    /**
     * Get alokasi kas for specific date
     *
     * @param string $tanggal YYYY-MM-DD
     * @return array{tanggal: string, total_pemasukan: string, total_kas_operasional: string}
     */
    public function getAlokasiKas(string $tanggal): array
    {
        $transaksis = TransaksiKeuangan::where('jenis', 'pemasukan')
            ->whereDate('tanggal', $tanggal)
            ->get();

        $totalPemasukan = '0.00';
        $totalKasOperasional = '0.00';

        foreach ($transaksis as $transaksi) {
            $totalPemasukan = bcadd($totalPemasukan, (string) $transaksi->jumlah, 2);
            $totalKasOperasional = bcadd($totalKasOperasional, (string) ($transaksi->kas_operasional ?? '0'), 2);
        }

        return [
            'tanggal' => $tanggal,
            'total_pemasukan' => $totalPemasukan,
            'total_kas_operasional' => $totalKasOperasional,
        ];
    }

    /**
     * Generate financial report for date range
     *
     * @param string $from YYYY-MM-DD
     * @param string $to YYYY-MM-DD
     * @return array
     */
    public function getLaporan(string $from, string $to): array
    {
        $pemasukans = TransaksiKeuangan::where('jenis', 'pemasukan')
            ->whereBetween('tanggal', [$from, $to])
            ->get();

        $pengeluarans = TransaksiKeuangan::where('jenis', 'pengeluaran')
            ->whereBetween('tanggal', [$from, $to])
            ->get();

        $totalPemasukan = '0.00';
        $totalKasOperasional = '0.00';
        $totalLabaBersih = '0.00';

        foreach ($pemasukans as $p) {
            $totalPemasukan = bcadd($totalPemasukan, (string) $p->jumlah, 2);
            $totalKasOperasional = bcadd($totalKasOperasional, (string) ($p->kas_operasional ?? '0'), 2);
            $totalLabaBersih = bcadd($totalLabaBersih, (string) ($p->laba_bersih ?? '0'), 2);
        }

        $totalPengeluaran = '0.00';
        foreach ($pengeluarans as $p) {
            $totalPengeluaran = bcadd($totalPengeluaran, (string) $p->jumlah, 2);
        }

        return [
            'periode' => [
                'from' => $from,
                'to' => $to,
            ],
            'total_pemasukan' => $totalPemasukan,
            'total_pengeluaran' => $totalPengeluaran,
            'total_kas_operasional' => $totalKasOperasional,
            'total_laba_bersih' => $totalLabaBersih,
            'selisih' => bcsub($totalPemasukan, $totalPengeluaran, 2),
        ];
    }
}
