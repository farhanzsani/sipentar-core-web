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
test('pemilik can access notifikasi', function () {
    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/notifikasi');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data notifikasi berhasil diambil.',
        ]);
});

test('pegawai can access notifikasi', function () {
    $response = $this->actingAs($this->pegawai)
        ->getJson('/api/v1/notifikasi');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data notifikasi berhasil diambil.',
        ]);
});

test('unauthenticated user cannot access notifikasi', function () {
    $this->getJson('/api/v1/notifikasi')->assertStatus(401);
});

// Notification Calculation Tests
test('notifikasi returns only stok where jumlah_stok <= batas_minimum', function () {
    // Low stock
    Stok::factory()->create([
        'nama_bahan' => 'Nutrisi A',
        'satuan' => 'liter',
        'jumlah_stok' => 5,
        'batas_minimum' => 10,
    ]);

    // Stock at minimum (should be included)
    Stok::factory()->create([
        'nama_bahan' => 'Bibit Selada',
        'satuan' => 'pack',
        'jumlah_stok' => 15,
        'batas_minimum' => 15,
    ]);

    // Adequate stock (should NOT be included)
    Stok::factory()->create([
        'nama_bahan' => 'Rockwool',
        'satuan' => 'lembar',
        'jumlah_stok' => 100,
        'batas_minimum' => 20,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/notifikasi');

    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect(count($data))->toBe(2);
});

test('notifikasi returns empty array when all stok above minimum', function () {
    Stok::factory()->count(5)->create([
        'jumlah_stok' => 100,
        'batas_minimum' => 20,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/notifikasi');

    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect($data)->toBeArray();
    expect(count($data))->toBe(0);
});

test('notifikasi includes stok with jumlah_stok equal to batas_minimum', function () {
    Stok::factory()->create([
        'nama_bahan' => 'Test Bahan',
        'jumlah_stok' => 25,
        'batas_minimum' => 25,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/notifikasi');

    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect(count($data))->toBe(1);
});

test('notifikasi does not include stok with jumlah_stok slightly above batas_minimum', function () {
    Stok::factory()->create([
        'nama_bahan' => 'Test Bahan',
        'jumlah_stok' => 25.01,
        'batas_minimum' => 25,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/notifikasi');

    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect(count($data))->toBe(0);
});

// Message Format Tests
test('notifikasi message format is correct', function () {
    $stok = Stok::factory()->create([
        'nama_bahan' => 'Nutrisi A',
        'satuan' => 'liter',
        'jumlah_stok' => 5,
        'batas_minimum' => 10,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/notifikasi');

    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect($data[0]['message'])->toBe("Stok Nutrisi A rendah. Sisa: 5.00 liter.");
});

test('notifikasi includes all required fields', function () {
    Stok::factory()->create([
        'nama_bahan' => 'Nutrisi B',
        'kategori' => 'Nutrisi',
        'satuan' => 'liter',
        'jumlah_stok' => 8,
        'batas_minimum' => 15,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/notifikasi');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'nama_bahan',
                    'kategori',
                    'jumlah_stok',
                    'batas_minimum',
                    'message',
                ],
            ],
        ]);
});

// Multiple Low Stock Items
test('notifikasi returns multiple low stock items correctly', function () {
    $stoks = [
        ['nama_bahan' => 'Nutrisi A', 'satuan' => 'liter', 'jumlah_stok' => 5, 'batas_minimum' => 10],
        ['nama_bahan' => 'Nutrisi B', 'satuan' => 'liter', 'jumlah_stok' => 3, 'batas_minimum' => 10],
        ['nama_bahan' => 'Bibit Selada', 'satuan' => 'pack', 'jumlah_stok' => 2, 'batas_minimum' => 5],
    ];

    foreach ($stoks as $stokData) {
        Stok::factory()->create($stokData);
    }

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/notifikasi');

    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect(count($data))->toBe(3);

    // Verify each has correct structure
    foreach ($data as $notif) {
        expect($notif)->toHaveKeys(['id', 'nama_bahan', 'kategori', 'jumlah_stok', 'batas_minimum', 'message']);
    }
});

// Edge Cases
test('notifikasi with zero stock', function () {
    Stok::factory()->create([
        'nama_bahan' => 'Empty Stock',
        'satuan' => 'kg',
        'jumlah_stok' => 0,
        'batas_minimum' => 10,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/notifikasi');

    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['message'])->toContain('Sisa: 0.00');
});

test('notifikasi with decimal stock values', function () {
    Stok::factory()->create([
        'nama_bahan' => 'Liquid Nutrient',
        'satuan' => 'liter',
        'jumlah_stok' => 7.50,
        'batas_minimum' => 10.00,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/notifikasi');

    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['jumlah_stok'])->toBe('7.50');
});

// Response Structure
test('notifikasi returns array not paginated', function () {
    Stok::factory()->count(3)->create([
        'jumlah_stok' => 5,
        'batas_minimum' => 10,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/notifikasi');

    $response->assertStatus(200);
    
    // Should be direct array, not paginated
    $response->assertJsonMissing(['current_page', 'per_page', 'total']);
    
    $data = $response->json('data');
    expect($data)->toBeArray();
});
