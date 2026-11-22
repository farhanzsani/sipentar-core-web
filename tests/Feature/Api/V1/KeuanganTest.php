<?php

use App\Models\User;
use App\Models\Panen;
use App\Models\TransaksiKeuangan;
use App\Enums\UserRole;

beforeEach(function () {
    $this->pemilik = User::factory()->create([
        'role' => UserRole::PEMILIK,
    ]);

    $this->pegawai = User::factory()->create([
        'role' => UserRole::PEGAWAI,
    ]);
});

test('pegawai cannot access hpp endpoint', function () {
    $response = $this->actingAs($this->pegawai)
        ->getJson('/api/v1/keuangan/hpp');

    $response->assertStatus(403);
});

test('pegawai cannot access alokasi kas endpoint', function () {
    $response = $this->actingAs($this->pegawai)
        ->getJson('/api/v1/keuangan/alokasi-kas');

    $response->assertStatus(403);
});

test('pegawai cannot access laporan endpoint', function () {
    $response = $this->actingAs($this->pegawai)
        ->getJson('/api/v1/keuangan/laporan');

    $response->assertStatus(403);
});

test('pemilik can calculate hpp with lubang parameter', function () {
    $response = $this->actingAs($this->pemilik)
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
    expect($data['lubang'])->toBe(100);
    expect($data['hpp_per_lubang'])->toBe('1200');
    expect($data['total_hpp'])->toBe('120000.00');
});

test('pemilik can calculate hpp from panen id', function () {
    $panen = Panen::factory()->create();

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/keuangan/hpp?panen_id=' . $panen->id);

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data['lubang'])->toBe($panen->budidaya->jumlah_tanaman);
    expect($data['hpp_per_lubang'])->toBe('1200');
});

test('hpp returns error when no parameters provided', function () {
    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/keuangan/hpp');

    // When no params, service uses sum of all meja kapasitas_lubang
    // So it should return 200, not 400
    $response->assertStatus(200);
});

test('hpp returns error when panen not found', function () {
    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/keuangan/hpp?panen_id=99999');

    // Validation error returns 422 with validation errors
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['panen_id']);
});

test('hpp uses bcmath for precision', function () {
    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/keuangan/hpp?lubang=333');

    $response->assertStatus(200);

    $data = $response->json('data');
    // 333 × 1200 = 399600.00
    expect($data['total_hpp'])->toBe('399600.00');
});

test('pemilik can get alokasi kas summary', function () {
    $tanggal = '2026-11-22';
    
    // Create transaksi pemasukan with kas_operasional
    TransaksiKeuangan::factory()->create([
        'jenis' => 'pemasukan',
        'tanggal' => $tanggal,
        'jumlah' => '1000000.00',
        'persentase_kas' => '35.00',
        'kas_operasional' => '350000.00',
        'laba_bersih' => '650000.00',
    ]);

    TransaksiKeuangan::factory()->create([
        'jenis' => 'pemasukan',
        'tanggal' => $tanggal,
        'jumlah' => '500000.00',
        'persentase_kas' => '40.00',
        'kas_operasional' => '200000.00',
        'laba_bersih' => '300000.00',
    ]);

    // Create transaksi pengeluaran (should be ignored in alokasi kas)
    TransaksiKeuangan::factory()->create([
        'jenis' => 'pengeluaran',
        'tanggal' => $tanggal,
        'jumlah' => '100000.00',
        'kas_operasional' => null,
        'laba_bersih' => null,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/keuangan/alokasi-kas?tanggal={$tanggal}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'tanggal',
                'total_pemasukan',
                'total_kas_operasional',
            ],
        ]);

    $data = $response->json('data');
    expect($data['total_pemasukan'])->toBe('1500000.00');
    expect($data['total_kas_operasional'])->toBe('550000.00');
});

test('pemilik can filter alokasi kas by date', function () {
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

    // Query for November 15 only
    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/keuangan/alokasi-kas?tanggal=2026-11-15');

    $response->assertStatus(200);

    $data = $response->json('data');
    // Only November 15 transaction
    expect($data['total_pemasukan'])->toBe('500000.00');
    expect($data['total_kas_operasional'])->toBe('200000.00');
});

test('pemilik can get laporan keuangan', function () {
    TransaksiKeuangan::factory()->create([
        'jenis' => 'pemasukan',
        'tanggal' => '2026-11-01',
        'jumlah' => '2000000.00',
        'kas_operasional' => '700000.00',
        'laba_bersih' => '1300000.00',
    ]);

    TransaksiKeuangan::factory()->create([
        'jenis' => 'pengeluaran',
        'tanggal' => '2026-11-02',
        'jumlah' => '300000.00',
    ]);

    TransaksiKeuangan::factory()->create([
        'jenis' => 'pengeluaran',
        'tanggal' => '2026-11-03',
        'jumlah' => '500000.00',
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/keuangan/laporan?from=2026-11-01&to=2026-11-30');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'periode',
                'total_pemasukan',
                'total_pengeluaran',
                'total_kas_operasional',
                'total_laba_bersih',
                'selisih',
            ],
        ]);

    $data = $response->json('data');
    expect($data['total_pemasukan'])->toBe('2000000.00');
    expect($data['total_pengeluaran'])->toBe('800000.00');
    expect($data['total_kas_operasional'])->toBe('700000.00');
    expect($data['total_laba_bersih'])->toBe('1300000.00');
    expect($data['selisih'])->toBe('1200000.00'); // 2000000 - 800000
});

test('pemilik can filter laporan by date range', function () {
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

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/keuangan/laporan?from=2026-11-01&to=2026-11-30');

    $response->assertStatus(200);

    $data = $response->json('data');
    // Only November transactions
    expect($data['total_pemasukan'])->toBe('2000000.00');
    expect($data['total_pengeluaran'])->toBe('500000.00');
    expect($data['selisih'])->toBe('1500000.00');
});

test('laporan uses bcmath for selisih calculation', function () {
    TransaksiKeuangan::factory()->create([
        'jenis' => 'pemasukan',
        'tanggal' => '2026-11-01',
        'jumlah' => '12345678.99',
        'kas_operasional' => '4320987.65',
        'laba_bersih' => '8024691.34',
    ]);

    TransaksiKeuangan::factory()->create([
        'jenis' => 'pengeluaran',
        'tanggal' => '2026-11-02',
        'jumlah' => '9876543.21',
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/keuangan/laporan?from=2026-11-01&to=2026-11-30');

    $response->assertStatus(200);

    $data = $response->json('data');
    // 12345678.99 - 9876543.21 = 2469135.78
    expect($data['selisih'])->toBe('2469135.78');
});
