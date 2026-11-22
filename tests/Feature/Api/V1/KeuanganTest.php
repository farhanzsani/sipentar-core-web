<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Panen;
use App\Models\TransaksiKeuangan;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeuanganTest extends TestCase
{
    use RefreshDatabase;

    private User $pemilik;
    private User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pemilik = User::factory()->create([
            'role' => UserRole::PEMILIK,
        ]);

        $this->pegawai = User::factory()->create([
            'role' => UserRole::PEGAWAI,
        ]);
    }

    /** @test */
    public function pegawai_cannot_access_hpp_endpoint()
    {
        $response = $this->actingAs($this->pegawai, 'sanctum')
            ->getJson('/api/v1/keuangan/hpp');

        $response->assertStatus(403);
    }

    /** @test */
    public function pegawai_cannot_access_alokasi_kas_endpoint()
    {
        $response = $this->actingAs($this->pegawai, 'sanctum')
            ->getJson('/api/v1/keuangan/alokasi-kas');

        $response->assertStatus(403);
    }

    /** @test */
    public function pegawai_cannot_access_laporan_endpoint()
    {
        $response = $this->actingAs($this->pegawai, 'sanctum')
            ->getJson('/api/v1/keuangan/laporan');

        $response->assertStatus(403);
    }

    /** @test */
    public function pemilik_can_calculate_hpp_with_lubang_parameter()
    {
        $response = $this->actingAs($this->pemilik, 'sanctum')
            ->getJson('/api/v1/keuangan/hpp?lubang=100');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'lubang',
                    'hpp_per_lubang',
                    'total_hpp',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(100, $data['lubang']);
        $this->assertEquals('1200.00', $data['hpp_per_lubang']);
        $this->assertEquals('120000.00', $data['total_hpp']);
    }

    /** @test */
    public function pemilik_can_calculate_hpp_from_panen_id()
    {
        $panen = Panen::factory()->create([
            'lubang_panen' => 150,
        ]);

        $response = $this->actingAs($this->pemilik, 'sanctum')
            ->getJson('/api/v1/keuangan/hpp?panen_id=' . $panen->id);

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(150, $data['lubang']);
        $this->assertEquals('1200.00', $data['hpp_per_lubang']);
        $this->assertEquals('180000.00', $data['total_hpp']);
    }

    /** @test */
    public function hpp_returns_error_when_no_parameters_provided()
    {
        $response = $this->actingAs($this->pemilik, 'sanctum')
            ->getJson('/api/v1/keuangan/hpp');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Parameter lubang atau panen_id harus diisi',
            ]);
    }

    /** @test */
    public function hpp_returns_error_when_panen_not_found()
    {
        $response = $this->actingAs($this->pemilik, 'sanctum')
            ->getJson('/api/v1/keuangan/hpp?panen_id=99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Data panen tidak ditemukan',
            ]);
    }

    /** @test */
    public function hpp_uses_bcmath_for_precision()
    {
        $response = $this->actingAs($this->pemilik, 'sanctum')
            ->getJson('/api/v1/keuangan/hpp?lubang=333');

        $response->assertStatus(200);

        $data = $response->json('data');
        // 333 × 1200 = 399600.00
        $this->assertEquals('399600.00', $data['total_hpp']);
    }

    /** @test */
    public function pemilik_can_get_alokasi_kas_summary()
    {
        // Create transaksi pemasukan with kas_operasional
        TransaksiKeuangan::factory()->create([
            'jenis' => 'pemasukan',
            'jumlah' => '1000000.00',
            'persentase_kas' => '35.00',
            'kas_operasional' => '350000.00',
            'laba_bersih' => '650000.00',
        ]);

        TransaksiKeuangan::factory()->create([
            'jenis' => 'pemasukan',
            'jumlah' => '500000.00',
            'persentase_kas' => '40.00',
            'kas_operasional' => '200000.00',
            'laba_bersih' => '300000.00',
        ]);

        // Create transaksi pengeluaran (should be ignored in alokasi kas)
        TransaksiKeuangan::factory()->create([
            'jenis' => 'pengeluaran',
            'jumlah' => '100000.00',
            'kas_operasional' => null,
            'laba_bersih' => null,
        ]);

        $response = $this->actingAs($this->pemilik, 'sanctum')
            ->getJson('/api/v1/keuangan/alokasi-kas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_pemasukan',
                    'total_kas_operasional',
                    'total_laba_bersih',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('1500000.00', $data['total_pemasukan']);
        $this->assertEquals('550000.00', $data['total_kas_operasional']);
        $this->assertEquals('950000.00', $data['total_laba_bersih']);
    }

    /** @test */
    public function pemilik_can_filter_alokasi_kas_by_date_range()
    {
        TransaksiKeuangan::factory()->create([
            'jenis' => 'pemasukan',
            'tanggal' => '2026-11-01',
            'jumlah' => '1000000.00',
            'kas_operasional' => '350000.00',
            'laba_bersih' => '650000.00',
        ]);

        TransaksiKeuangan::factory()->create([
            'jenis' => 'pemasukan',
            'tanggal' => '2026-11-15',
            'jumlah' => '500000.00',
            'kas_operasional' => '200000.00',
            'laba_bersih' => '300000.00',
        ]);

        TransaksiKeuangan::factory()->create([
            'jenis' => 'pemasukan',
            'tanggal' => '2026-12-01',
            'jumlah' => '800000.00',
            'kas_operasional' => '280000.00',
            'laba_bersih' => '520000.00',
        ]);

        $response = $this->actingAs($this->pemilik, 'sanctum')
            ->getJson('/api/v1/keuangan/alokasi-kas?dari=2026-11-01&sampai=2026-11-30');

        $response->assertStatus(200);

        $data = $response->json('data');
        // Only November transactions
        $this->assertEquals('1500000.00', $data['total_pemasukan']);
        $this->assertEquals('550000.00', $data['total_kas_operasional']);
        $this->assertEquals('950000.00', $data['total_laba_bersih']);
    }

    /** @test */
    public function pemilik_can_get_laporan_keuangan()
    {
        TransaksiKeuangan::factory()->create([
            'jenis' => 'pemasukan',
            'kategori' => 'penjualan',
            'jumlah' => '2000000.00',
            'kas_operasional' => '700000.00',
            'laba_bersih' => '1300000.00',
        ]);

        TransaksiKeuangan::factory()->create([
            'jenis' => 'pengeluaran',
            'kategori' => 'operasional',
            'jumlah' => '300000.00',
        ]);

        TransaksiKeuangan::factory()->create([
            'jenis' => 'pengeluaran',
            'kategori' => 'investasi',
            'jumlah' => '500000.00',
        ]);

        $response = $this->actingAs($this->pemilik, 'sanctum')
            ->getJson('/api/v1/keuangan/laporan');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_pemasukan',
                    'total_pengeluaran',
                    'total_kas_operasional',
                    'total_laba_bersih',
                    'saldo',
                    'pemasukan_per_kategori',
                    'pengeluaran_per_kategori',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('2000000.00', $data['total_pemasukan']);
        $this->assertEquals('800000.00', $data['total_pengeluaran']);
        $this->assertEquals('700000.00', $data['total_kas_operasional']);
        $this->assertEquals('1300000.00', $data['total_laba_bersih']);
        $this->assertEquals('1200000.00', $data['saldo']); // 2000000 - 800000

        // Check kategori breakdown
        $this->assertArrayHasKey('penjualan', $data['pemasukan_per_kategori']);
        $this->assertEquals('2000000.00', $data['pemasukan_per_kategori']['penjualan']);

        $this->assertArrayHasKey('operasional', $data['pengeluaran_per_kategori']);
        $this->assertArrayHasKey('investasi', $data['pengeluaran_per_kategori']);
        $this->assertEquals('300000.00', $data['pengeluaran_per_kategori']['operasional']);
        $this->assertEquals('500000.00', $data['pengeluaran_per_kategori']['investasi']);
    }

    /** @test */
    public function pemilik_can_filter_laporan_by_date_range()
    {
        TransaksiKeuangan::factory()->create([
            'jenis' => 'pemasukan',
            'tanggal' => '2026-10-15',
            'jumlah' => '1000000.00',
            'kas_operasional' => '350000.00',
            'laba_bersih' => '650000.00',
        ]);

        TransaksiKeuangan::factory()->create([
            'jenis' => 'pemasukan',
            'tanggal' => '2026-11-15',
            'jumlah' => '2000000.00',
            'kas_operasional' => '700000.00',
            'laba_bersih' => '1300000.00',
        ]);

        TransaksiKeuangan::factory()->create([
            'jenis' => 'pengeluaran',
            'tanggal' => '2026-11-20',
            'jumlah' => '500000.00',
        ]);

        $response = $this->actingAs($this->pemilik, 'sanctum')
            ->getJson('/api/v1/keuangan/laporan?dari=2026-11-01&sampai=2026-11-30');

        $response->assertStatus(200);

        $data = $response->json('data');
        // Only November transactions
        $this->assertEquals('2000000.00', $data['total_pemasukan']);
        $this->assertEquals('500000.00', $data['total_pengeluaran']);
        $this->assertEquals('1500000.00', $data['saldo']);
    }

    /** @test */
    public function laporan_uses_bcmath_for_saldo_calculation()
    {
        TransaksiKeuangan::factory()->create([
            'jenis' => 'pemasukan',
            'jumlah' => '12345678.99',
            'kas_operasional' => '4320987.65',
            'laba_bersih' => '8024691.34',
        ]);

        TransaksiKeuangan::factory()->create([
            'jenis' => 'pengeluaran',
            'jumlah' => '9876543.21',
        ]);

        $response = $this->actingAs($this->pemilik, 'sanctum')
            ->getJson('/api/v1/keuangan/laporan');

        $response->assertStatus(200);

        $data = $response->json('data');
        // 12345678.99 - 9876543.21 = 2469135.78
        $this->assertEquals('2469135.78', $data['saldo']);
    }
}
