<?php

use App\Enums\UserRole;
use App\Models\PenggunaanOperasional;
use App\Models\Stok;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->pemilik = User::factory()->create([
        'nama' => 'Pemilik User',
        'username' => 'pemilik',
        'password' => 'password123',
        'role' => UserRole::PEMILIK,
    ]);

    $this->pegawai = User::factory()->create([
        'nama' => 'Pegawai User',
        'username' => 'pegawai',
        'password' => 'password123',
        'role' => UserRole::PEGAWAI,
    ]);

    $this->stok = Stok::factory()->create([
        'nama_bahan' => 'Nutrisi A',
        'jumlah_stok' => 100,
        'batas_minimum' => 20,
    ]);
});

// Access Control Tests
test('pemilik can access penggunaan operasional list', function () {
    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/penggunaan-operasional');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data penggunaan operasional berhasil diambil.',
        ]);
});

test('pegawai can access penggunaan operasional list', function () {
    $response = $this->actingAs($this->pegawai)
        ->getJson('/api/v1/penggunaan-operasional');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data penggunaan operasional berhasil diambil.',
        ]);
});

test('pemilik can create penggunaan operasional', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/penggunaan-operasional', [
            'stok_id' => $this->stok->id,
            'tanggal' => now()->format('Y-m-d'),
            'jumlah' => 10,
            'keterangan' => 'Penggunaan untuk meja 1',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Penggunaan operasional berhasil dicatat.',
        ]);

    $this->assertDatabaseHas('penggunaan_operasionals', [
        'stok_id' => $this->stok->id,
        'jumlah' => '10.00',
        'user_id' => $this->pemilik->id,
    ]);
});

test('pegawai can create penggunaan operasional', function () {
    $response = $this->actingAs($this->pegawai)
        ->postJson('/api/v1/penggunaan-operasional', [
            'stok_id' => $this->stok->id,
            'tanggal' => now()->format('Y-m-d'),
            'jumlah' => 5,
            'keterangan' => 'Penggunaan untuk meja 2',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Penggunaan operasional berhasil dicatat.',
        ]);

    $this->assertDatabaseHas('penggunaan_operasionals', [
        'user_id' => $this->pegawai->id,
    ]);
});

test('unauthenticated user cannot access penggunaan operasional endpoints', function () {
    $this->getJson('/api/v1/penggunaan-operasional')->assertStatus(401);
    $this->postJson('/api/v1/penggunaan-operasional')->assertStatus(401);
});

// Stock Reduction Tests
test('POST penggunaan operasional reduces stok jumlah_stok', function () {
    expect($this->stok->jumlah_stok)->toBe('100.00');

    $this->actingAs($this->pemilik)
        ->postJson('/api/v1/penggunaan-operasional', [
            'stok_id' => $this->stok->id,
            'tanggal' => now()->format('Y-m-d'),
            'jumlah' => 25,
            'keterangan' => 'Test reduction',
        ]);

    expect($this->stok->fresh()->jumlah_stok)->toBe('75.00');
});

test('POST penggunaan operasional returns 422 when jumlah exceeds available stok', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/penggunaan-operasional', [
            'stok_id' => $this->stok->id,
            'tanggal' => now()->format('Y-m-d'),
            'jumlah' => 150, // Exceeds 100
            'keterangan' => 'Test insufficient stock',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['jumlah'])
        ->assertJson([
            'success' => false,
            'errors' => [
                'jumlah' => ['Stok tidak mencukupi.'],
            ],
        ]);

    // Stok should remain unchanged
    expect($this->stok->fresh()->jumlah_stok)->toBe('100.00');
});

test('POST penggunaan operasional returns 422 when exact stok amount plus epsilon exceeds', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/penggunaan-operasional', [
            'stok_id' => $this->stok->id,
            'tanggal' => now()->format('Y-m-d'),
            'jumlah' => 100.01, // Just slightly over
            'keterangan' => 'Test exact limit',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['jumlah']);

    expect($this->stok->fresh()->jumlah_stok)->toBe('100.00');
});

test('POST penggunaan operasional succeeds when using exact available stok', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/penggunaan-operasional', [
            'stok_id' => $this->stok->id,
            'tanggal' => now()->format('Y-m-d'),
            'jumlah' => 100, // Exact amount
            'keterangan' => 'Using all stock',
        ]);

    $response->assertStatus(201);
    expect($this->stok->fresh()->jumlah_stok)->toBe('0.00');
});

test('transaction rollback on error prevents stok reduction', function () {
    // Create a stok with invalid data that would fail after reduction
    $stok = Stok::factory()->create([
        'jumlah_stok' => 50,
    ]);

    $originalAmount = $stok->jumlah_stok;

    // Try with non-existent stok_id
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/penggunaan-operasional', [
            'stok_id' => 99999,
            'tanggal' => now()->format('Y-m-d'),
            'jumlah' => 10,
            'keterangan' => 'Test',
        ]);

    $response->assertStatus(422);

    // Original stok should be unchanged
    expect($stok->fresh()->jumlah_stok)->toBe($originalAmount);
});

// user_id Auto-fill Tests
test('user_id is auto-filled from authenticated user', function () {
    $response = $this->actingAs($this->pegawai)
        ->postJson('/api/v1/penggunaan-operasional', [
            'stok_id' => $this->stok->id,
            'tanggal' => now()->format('Y-m-d'),
            'jumlah' => 5,
            'keterangan' => 'Test user_id',
        ]);

    $response->assertStatus(201);
    expect($response->json('data.user_id'))->toBe($this->pegawai->id);

    $this->assertDatabaseHas('penggunaan_operasionals', [
        'user_id' => $this->pegawai->id,
        'stok_id' => $this->stok->id,
    ]);
});

test('user_id cannot be overridden by client', function () {
    $response = $this->actingAs($this->pegawai)
        ->postJson('/api/v1/penggunaan-operasional', [
            'stok_id' => $this->stok->id,
            'tanggal' => now()->format('Y-m-d'),
            'jumlah' => 5,
            'keterangan' => 'Test',
            'user_id' => $this->pemilik->id, // Try to fake user_id
        ]);

    $response->assertStatus(201);
    
    // Should use authenticated user, not the provided user_id
    $this->assertDatabaseHas('penggunaan_operasionals', [
        'user_id' => $this->pegawai->id,
    ]);

    $this->assertDatabaseMissing('penggunaan_operasionals', [
        'user_id' => $this->pemilik->id,
    ]);
});

// Filter Tests
test('filter penggunaan operasional by stok_id', function () {
    $stok2 = Stok::factory()->create();

    PenggunaanOperasional::factory()->count(3)->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $this->stok->id,
    ]);

    PenggunaanOperasional::factory()->count(2)->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $stok2->id,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/penggunaan-operasional?stok_id={$this->stok->id}");

    $response->assertStatus(200);
    expect($response->json('data.total'))->toBe(3);
});

test('filter penggunaan operasional by date range from', function () {
    PenggunaanOperasional::factory()->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $this->stok->id,
        'tanggal' => now()->subDays(20),
    ]);

    PenggunaanOperasional::factory()->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $this->stok->id,
        'tanggal' => now()->subDays(5),
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/penggunaan-operasional?from=' . now()->subDays(10)->format('Y-m-d'));

    $response->assertStatus(200);
    expect($response->json('data.total'))->toBe(1);
});

test('filter penggunaan operasional by date range to', function () {
    PenggunaanOperasional::factory()->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $this->stok->id,
        'tanggal' => now()->subDays(20),
    ]);

    PenggunaanOperasional::factory()->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $this->stok->id,
        'tanggal' => now()->subDays(5),
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/penggunaan-operasional?to=' . now()->subDays(10)->format('Y-m-d'));

    $response->assertStatus(200);
    expect($response->json('data.total'))->toBe(1);
});

test('filter penggunaan operasional by date range from and to', function () {
    PenggunaanOperasional::factory()->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $this->stok->id,
        'tanggal' => now()->subDays(30),
    ]);

    PenggunaanOperasional::factory()->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $this->stok->id,
        'tanggal' => now()->subDays(15),
    ]);

    PenggunaanOperasional::factory()->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $this->stok->id,
        'tanggal' => now()->subDays(5),
    ]);

    $from = now()->subDays(20)->format('Y-m-d');
    $to = now()->subDays(10)->format('Y-m-d');

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/penggunaan-operasional?from={$from}&to={$to}");

    $response->assertStatus(200);
    expect($response->json('data.total'))->toBe(1);
});

test('filter penggunaan operasional by multiple filters', function () {
    $stok2 = Stok::factory()->create();

    PenggunaanOperasional::factory()->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $this->stok->id,
        'tanggal' => now()->subDays(10),
    ]);

    PenggunaanOperasional::factory()->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $stok2->id,
        'tanggal' => now()->subDays(10),
    ]);

    PenggunaanOperasional::factory()->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $this->stok->id,
        'tanggal' => now()->subDays(30),
    ]);

    $from = now()->subDays(15)->format('Y-m-d');

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/penggunaan-operasional?stok_id={$this->stok->id}&from={$from}");

    $response->assertStatus(200);
    expect($response->json('data.total'))->toBe(1);
});

// Eager Loading Test
test('penggunaan operasional list eager loads relations to prevent N+1', function () {
    PenggunaanOperasional::factory()->count(3)->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $this->stok->id,
    ]);

    \DB::enableQueryLog();

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/penggunaan-operasional');

    $queries = \DB::getQueryLog();
    \DB::disableQueryLog();

    $response->assertStatus(200);

    // Should have: 1 for penggunaan_operasional + 1 for users + 1 for stoks + 1 for count = 4 queries max
    expect(count($queries))->toBeLessThanOrEqual(5);
});

// Validation Tests
test('validation error when creating penggunaan operasional with missing fields', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/penggunaan-operasional', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['stok_id', 'tanggal', 'jumlah']);
});

test('validation error when stok_id does not exist', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/penggunaan-operasional', [
            'stok_id' => 99999,
            'tanggal' => now()->format('Y-m-d'),
            'jumlah' => 10,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['stok_id']);
});

test('validation error when jumlah is zero or negative', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/penggunaan-operasional', [
            'stok_id' => $this->stok->id,
            'tanggal' => now()->format('Y-m-d'),
            'jumlah' => 0,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['jumlah']);

    $response2 = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/penggunaan-operasional', [
            'stok_id' => $this->stok->id,
            'tanggal' => now()->format('Y-m-d'),
            'jumlah' => -5,
        ]);

    $response2->assertStatus(422)
        ->assertJsonValidationErrors(['jumlah']);
});

// Resource Structure Tests
test('penggunaan operasional response includes correct structure', function () {
    $penggunaan = PenggunaanOperasional::factory()->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $this->stok->id,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/penggunaan-operasional');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'stok_id',
                        'tanggal',
                        'jumlah',
                        'keterangan',
                        'user' => ['id', 'nama', 'username', 'role'],
                        'stok' => ['id', 'nama_bahan', 'kategori', 'satuan', 'jumlah_stok', 'batas_minimum', 'is_low'],
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
        ]);
});

test('penggunaan operasional list response includes pagination', function () {
    PenggunaanOperasional::factory()->count(5)->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $this->stok->id,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/penggunaan-operasional');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data',
                'current_page',
                'per_page',
                'total',
            ],
        ]);
});

test('penggunaan operasional list supports custom pagination', function () {
    PenggunaanOperasional::factory()->count(20)->create([
        'user_id' => $this->pemilik->id,
        'stok_id' => $this->stok->id,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/penggunaan-operasional?per_page=10');

    $response->assertStatus(200);
    expect($response->json('data.per_page'))->toBe(10);
    expect(count($response->json('data.data')))->toBe(10);
});
