<?php

use App\Enums\UserRole;
use App\Models\Panen;
use App\Models\TransaksiKeuangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->pemilik = User::factory()->create(['role' => UserRole::PEMILIK]);
    $this->pegawai = User::factory()->create(['role' => UserRole::PEGAWAI]);
});

test('pemilik can list transaksi keuangan', function () {
    TransaksiKeuangan::factory()->count(3)->create(['user_id' => $this->pemilik->id]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/transaksi-keuangan');

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure([
            'data' => [
                'data' => [
                    '*' => ['id', 'jenis', 'tanggal', 'jumlah'],
                ],
            ],
        ]);
});

test('pegawai cannot access transaksi keuangan', function () {
    $response = $this->actingAs($this->pegawai)
        ->getJson('/api/v1/transaksi-keuangan');

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Anda tidak memiliki akses ke resource ini.',
        ]);
});

test('pemilik can create pemasukan with auto-calculated fields', function () {
    $data = [
        'jenis' => 'pemasukan',
        'tanggal' => '2024-01-15',
        'jumlah' => 1000000,
        'persentase_kas' => 35,
        'keterangan' => 'Penjualan hasil panen',
    ];

    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/transaksi-keuangan', $data);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'jenis' => 'pemasukan',
                'jumlah' => '1000000.00',
                'persentase_kas' => '35.00',
                'kas_operasional' => '350000.00',
                'laba_bersih' => '650000.00',
            ],
        ]);

    $this->assertDatabaseHas('transaksi_keuangans', [
        'jenis' => 'pemasukan',
        'jumlah' => '1000000.00',
        'kas_operasional' => '350000.00',
        'laba_bersih' => '650000.00',
    ]);
});

test('pemasukan calculation uses bcmath for precision', function () {
    $data = [
        'jenis' => 'pemasukan',
        'tanggal' => '2024-01-15',
        'jumlah' => 1234567.89,
        'persentase_kas' => 37.5,
        'keterangan' => 'Test precision',
    ];

    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/transaksi-keuangan', $data);

    $response->assertStatus(201);

    // Manual calculation with bcmath (truncate, not round):
    // kas_operasional: 1234567.89 * 37.5 / 100 = 462962.95875 → truncate to 462962.95
    // laba_bersih: 1234567.89 - 462962.95 = 771604.94
    $this->assertDatabaseHas('transaksi_keuangans', [
        'jumlah' => '1234567.89',
        'kas_operasional' => '462962.95',
        'laba_bersih' => '771604.94',
    ]);
});

test('pemilik can create pengeluaran with null financial fields', function () {
    $data = [
        'jenis' => 'pengeluaran',
        'tanggal' => '2024-01-15',
        'jumlah' => 500000,
        'keterangan' => 'Pembelian pupuk',
    ];

    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/transaksi-keuangan', $data);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'jenis' => 'pengeluaran',
                'persentase_kas' => null,
                'kas_operasional' => null,
                'laba_bersih' => null,
            ],
        ]);
});

test('persentase_kas validation enforces min and max from env', function () {
    $data = [
        'jenis' => 'pemasukan',
        'tanggal' => '2024-01-15',
        'jumlah' => 1000000,
        'persentase_kas' => 25, // Below min (30)
    ];

    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/transaksi-keuangan', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['persentase_kas']);
});

test('persentase_kas required for pemasukan', function () {
    $data = [
        'jenis' => 'pemasukan',
        'tanggal' => '2024-01-15',
        'jumlah' => 1000000,
        // persentase_kas missing
    ];

    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/transaksi-keuangan', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['persentase_kas']);
});

test('user_id auto-filled from auth', function () {
    $data = [
        'jenis' => 'pengeluaran',
        'tanggal' => '2024-01-15',
        'jumlah' => 100000,
    ];

    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/transaksi-keuangan', $data);

    $response->assertStatus(201);

    $this->assertDatabaseHas('transaksi_keuangans', [
        'user_id' => $this->pemilik->id,
    ]);
});

test('pemilik can update transaksi keuangan with recalculation', function () {
    $transaksi = TransaksiKeuangan::factory()->create([
        'user_id' => $this->pemilik->id,
        'jenis' => 'pemasukan',
        'jumlah' => 1000000,
        'persentase_kas' => 35,
        'kas_operasional' => 350000,
        'laba_bersih' => 650000,
    ]);

    $updateData = [
        'jenis' => 'pemasukan',
        'tanggal' => $transaksi->tanggal->format('Y-m-d'),
        'jumlah' => 2000000,
        'persentase_kas' => 40,
        'keterangan' => 'Updated',
    ];

    $response = $this->actingAs($this->pemilik)
        ->putJson("/api/v1/transaksi-keuangan/{$transaksi->id}", $updateData);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'jumlah' => '2000000.00',
                'kas_operasional' => '800000.00',
                'laba_bersih' => '1200000.00',
            ],
        ]);
});

test('filter by jenis works', function () {
    TransaksiKeuangan::factory()->create(['jenis' => 'pemasukan', 'user_id' => $this->pemilik->id]);
    TransaksiKeuangan::factory()->create(['jenis' => 'pengeluaran', 'user_id' => $this->pemilik->id]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/transaksi-keuangan?jenis=pemasukan');

    $response->assertStatus(200);
    $data = $response->json('data.data');
    
    expect($data)->toHaveCount(1);
    expect($data[0]['jenis'])->toBe('pemasukan');
});

test('filter by date range works', function () {
    TransaksiKeuangan::factory()->create([
        'tanggal' => '2024-01-10',
        'user_id' => $this->pemilik->id,
    ]);
    TransaksiKeuangan::factory()->create([
        'tanggal' => '2024-01-20',
        'user_id' => $this->pemilik->id,
    ]);
    TransaksiKeuangan::factory()->create([
        'tanggal' => '2024-01-30',
        'user_id' => $this->pemilik->id,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/transaksi-keuangan?from=2024-01-15&to=2024-01-25');

    $response->assertStatus(200);
    $data = $response->json('data.data');
    
    expect($data)->toHaveCount(1);
});

test('pemilik can view single transaksi', function () {
    $transaksi = TransaksiKeuangan::factory()->create(['user_id' => $this->pemilik->id]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/transaksi-keuangan/{$transaksi->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => ['id' => $transaksi->id],
        ]);
});

test('eager loading prevents N+1 queries', function () {
    TransaksiKeuangan::factory()->count(5)->create(['user_id' => $this->pemilik->id]);

    $queryCount = 0;
    \DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $this->actingAs($this->pemilik)
        ->getJson('/api/v1/transaksi-keuangan');

    // Should be: 1 query for transaksis + 1 for users + 1 for panens (or less with optimization)
    expect($queryCount)->toBeLessThan(10);
});
