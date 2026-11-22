<?php

use App\Enums\UserRole;
use App\Models\MejaTanam;
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

test('pemilik can list meja tanam', function () {
    MejaTanam::factory()->count(3)->create();

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/meja-tanam');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data meja tanam berhasil diambil.',
        ])
        ->assertJsonStructure([
            'data' => [
                'data' => [
                    '*' => ['id', 'nomor_meja', 'kapasitas_lubang', 'status', 'created_at', 'updated_at'],
                ],
                'current_page',
                'per_page',
                'total',
            ],
        ]);

    expect($response->json('data.total'))->toBe(3);
});

test('pegawai can list meja tanam', function () {
    MejaTanam::factory()->count(5)->create();

    $response = $this->actingAs($this->pegawai)
        ->getJson('/api/v1/meja-tanam');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data meja tanam berhasil diambil.',
        ]);

    expect($response->json('data.total'))->toBe(5);
});

test('unauthenticated user cannot access meja tanam list', function () {
    $response = $this->getJson('/api/v1/meja-tanam');

    $response->assertStatus(401);
});

test('meja tanam list supports pagination', function () {
    MejaTanam::factory()->count(20)->create();

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/meja-tanam?per_page=10');

    $response->assertStatus(200);
    expect($response->json('data.per_page'))->toBe(10);
    expect(count($response->json('data.data')))->toBe(10);
});

test('meja tanam list shows correct data structure', function () {
    $meja = MejaTanam::factory()->create([
        'nomor_meja' => 5,
        'kapasitas_lubang' => 250,
        'status' => 'kosong',
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/meja-tanam');

    $response->assertStatus(200);

    $mejaData = collect($response->json('data.data'))
        ->firstWhere('id', $meja->id);

    expect($mejaData)->not->toBeNull();
    expect($mejaData['nomor_meja'])->toBe(5);
    expect($mejaData['kapasitas_lubang'])->toBe(250);
    expect($mejaData['status'])->toBe('kosong');
});

test('meja tanam list returns all meja regardless of status', function () {
    MejaTanam::factory()->create(['status' => 'kosong']);
    MejaTanam::factory()->create(['status' => 'terisi']);
    MejaTanam::factory()->create(['status' => 'kosong']);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/meja-tanam');

    $response->assertStatus(200);
    expect($response->json('data.total'))->toBe(3);
});

test('meja tanam default pagination is 15 per page', function () {
    MejaTanam::factory()->count(20)->create();

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/meja-tanam');

    $response->assertStatus(200);
    expect($response->json('data.per_page'))->toBe(15);
    expect(count($response->json('data.data')))->toBe(15);
});

