<?php

use App\Enums\UserRole;
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
});

// Access Control Tests
test('pemilik can access stok list', function () {
    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/stok');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data stok berhasil diambil.',
        ]);
});

test('pegawai can access stok list', function () {
    $response = $this->actingAs($this->pegawai)
        ->getJson('/api/v1/stok');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data stok berhasil diambil.',
        ]);
});

test('pemilik can create stok', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/stok', [
            'nama_bahan' => 'Nutrisi A',
            'kategori' => 'Nutrisi',
            'satuan' => 'liter',
            'jumlah_stok' => 100,
            'batas_minimum' => 20,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Stok berhasil dibuat.',
        ]);

    $this->assertDatabaseHas('stoks', [
        'nama_bahan' => 'Nutrisi A',
        'kategori' => 'Nutrisi',
    ]);
});

test('pegawai can create stok', function () {
    $response = $this->actingAs($this->pegawai)
        ->postJson('/api/v1/stok', [
            'nama_bahan' => 'Bibit Selada',
            'kategori' => 'Bibit',
            'satuan' => 'pack',
            'jumlah_stok' => 50,
            'batas_minimum' => 10,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Stok berhasil dibuat.',
        ]);
});

test('pemilik can view single stok', function () {
    $stok = Stok::factory()->create();

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/stok/{$stok->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data stok berhasil diambil.',
        ]);
});

test('pegawai can view single stok', function () {
    $stok = Stok::factory()->create();

    $response = $this->actingAs($this->pegawai)
        ->getJson("/api/v1/stok/{$stok->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data stok berhasil diambil.',
        ]);
});

test('pemilik can update stok', function () {
    $stok = Stok::factory()->create();

    $response = $this->actingAs($this->pemilik)
        ->putJson("/api/v1/stok/{$stok->id}", [
            'nama_bahan' => 'Nutrisi B Updated',
            'kategori' => 'Nutrisi',
            'satuan' => 'liter',
            'jumlah_stok' => 80,
            'batas_minimum' => 15,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Stok berhasil diperbarui.',
        ]);

    $this->assertDatabaseHas('stoks', [
        'id' => $stok->id,
        'nama_bahan' => 'Nutrisi B Updated',
    ]);
});

test('pegawai can update stok', function () {
    $stok = Stok::factory()->create();

    $response = $this->actingAs($this->pegawai)
        ->putJson("/api/v1/stok/{$stok->id}", [
            'nama_bahan' => 'Bibit Updated',
            'kategori' => 'Bibit',
            'satuan' => 'pack',
            'jumlah_stok' => 30,
            'batas_minimum' => 5,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Stok berhasil diperbarui.',
        ]);
});

test('unauthenticated user cannot access stok endpoints', function () {
    $this->getJson('/api/v1/stok')->assertStatus(401);
    $this->postJson('/api/v1/stok')->assertStatus(401);
    $this->getJson('/api/v1/stok/1')->assertStatus(401);
    $this->putJson('/api/v1/stok/1')->assertStatus(401);
});

// is_low Flag Tests
test('is_low is true when jumlah_stok equals batas_minimum', function () {
    $stok = Stok::factory()->create([
        'jumlah_stok' => 10,
        'batas_minimum' => 10,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/stok/{$stok->id}");

    $response->assertStatus(200);
    expect($response->json('data.is_low'))->toBe(true);
});

test('is_low is true when jumlah_stok is below batas_minimum', function () {
    $stok = Stok::factory()->create([
        'jumlah_stok' => 5,
        'batas_minimum' => 10,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/stok/{$stok->id}");

    $response->assertStatus(200);
    expect($response->json('data.is_low'))->toBe(true);
});

test('is_low is false when jumlah_stok is above batas_minimum', function () {
    $stok = Stok::factory()->create([
        'jumlah_stok' => 50,
        'batas_minimum' => 10,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/stok/{$stok->id}");

    $response->assertStatus(200);
    expect($response->json('data.is_low'))->toBe(false);
});

test('is_low flag is included in stok list', function () {
    Stok::factory()->create([
        'jumlah_stok' => 5,
        'batas_minimum' => 10,
    ]);

    Stok::factory()->create([
        'jumlah_stok' => 50,
        'batas_minimum' => 10,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/stok');

    $response->assertStatus(200);
    
    $data = $response->json('data.data');
    expect($data[0])->toHaveKey('is_low');
    expect($data[1])->toHaveKey('is_low');
});

// Validation Tests
test('validation error when creating stok with missing fields', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/stok', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['nama_bahan', 'kategori', 'satuan', 'jumlah_stok', 'batas_minimum']);
});

test('validation error when jumlah_stok is negative', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/stok', [
            'nama_bahan' => 'Nutrisi A',
            'kategori' => 'Nutrisi',
            'satuan' => 'liter',
            'jumlah_stok' => -10,
            'batas_minimum' => 20,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['jumlah_stok']);
});

test('validation error when batas_minimum is negative', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/stok', [
            'nama_bahan' => 'Nutrisi A',
            'kategori' => 'Nutrisi',
            'satuan' => 'liter',
            'jumlah_stok' => 100,
            'batas_minimum' => -5,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['batas_minimum']);
});

// Resource Structure Tests
test('stok response includes correct structure', function () {
    $stok = Stok::factory()->create();

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/stok/{$stok->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'nama_bahan',
                'kategori',
                'satuan',
                'jumlah_stok',
                'batas_minimum',
                'is_low',
                'created_at',
                'updated_at',
            ],
        ]);
});

test('stok list response includes pagination', function () {
    Stok::factory()->count(5)->create();

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/stok');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data' => [
                    '*' => ['id', 'nama_bahan', 'kategori', 'satuan', 'jumlah_stok', 'batas_minimum', 'is_low'],
                ],
                'current_page',
                'per_page',
                'total',
            ],
        ]);
});

test('stok list supports custom pagination', function () {
    Stok::factory()->count(20)->create();

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/stok?per_page=10');

    $response->assertStatus(200);
    expect($response->json('data.per_page'))->toBe(10);
    expect(count($response->json('data.data')))->toBe(10);
});
