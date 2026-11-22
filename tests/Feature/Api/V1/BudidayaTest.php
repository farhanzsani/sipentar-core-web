<?php

use App\Enums\UserRole;
use App\Models\Budidaya;
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

    $this->meja = MejaTanam::factory()->create([
        'nomor_meja' => 1,
        'kapasitas_lubang' => 100,
        'status' => 'kosong',
    ]);
});

// Access Control Tests
test('pemilik can access budidaya list', function () {
    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/budidaya');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data budidaya berhasil diambil.',
        ]);
});

test('pegawai can access budidaya list', function () {
    $response = $this->actingAs($this->pegawai)
        ->getJson('/api/v1/budidaya');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data budidaya berhasil diambil.',
        ]);
});

test('pemilik can create budidaya', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/budidaya', [
            'meja_id' => $this->meja->id,
            'tanggal_semai' => now()->subDays(10)->format('Y-m-d'),
            'jumlah_tanaman' => 50,
            'status' => 'berjalan',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Budidaya berhasil dibuat.',
        ]);
});

test('pegawai can create budidaya', function () {
    $response = $this->actingAs($this->pegawai)
        ->postJson('/api/v1/budidaya', [
            'meja_id' => $this->meja->id,
            'tanggal_semai' => now()->subDays(5)->format('Y-m-d'),
            'jumlah_tanaman' => 30,
            'status' => 'berjalan',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Budidaya berhasil dibuat.',
        ]);
});

test('pemilik can view single budidaya', function () {
    $budidaya = Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/budidaya/{$budidaya->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data budidaya berhasil diambil.',
        ]);
});

test('pegawai can view single budidaya', function () {
    $budidaya = Budidaya::factory()->create([
        'user_id' => $this->pegawai->id,
        'meja_id' => $this->meja->id,
    ]);

    $response = $this->actingAs($this->pegawai)
        ->getJson("/api/v1/budidaya/{$budidaya->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data budidaya berhasil diambil.',
        ]);
});

test('pemilik can update budidaya', function () {
    $budidaya = Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->putJson("/api/v1/budidaya/{$budidaya->id}", [
            'meja_id' => $this->meja->id,
            'tanggal_semai' => now()->subDays(5)->format('Y-m-d'),
            'jumlah_tanaman' => 40,
            'status' => 'selesai',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Budidaya berhasil diperbarui.',
        ]);
});

test('pegawai can update budidaya', function () {
    $budidaya = Budidaya::factory()->create([
        'user_id' => $this->pegawai->id,
        'meja_id' => $this->meja->id,
    ]);

    $response = $this->actingAs($this->pegawai)
        ->putJson("/api/v1/budidaya/{$budidaya->id}", [
            'meja_id' => $this->meja->id,
            'tanggal_semai' => now()->subDays(3)->format('Y-m-d'),
            'jumlah_tanaman' => 25,
            'status' => 'selesai',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Budidaya berhasil diperbarui.',
        ]);
});

test('unauthenticated user cannot access budidaya endpoints', function () {
    $this->getJson('/api/v1/budidaya')->assertStatus(401);
    $this->postJson('/api/v1/budidaya')->assertStatus(401);
    $this->getJson('/api/v1/budidaya/1')->assertStatus(401);
    $this->putJson('/api/v1/budidaya/1')->assertStatus(401);
});

// HSS Calculation Tests
test('hss is calculated correctly for 10 days old budidaya', function () {
    $budidaya = Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'tanggal_semai' => now()->subDays(10),
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/budidaya/{$budidaya->id}");

    $response->assertStatus(200);
    expect($response->json('data.hss'))->toBe(10);
});

test('hss is calculated correctly for today budidaya', function () {
    $budidaya = Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'tanggal_semai' => now(),
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/budidaya/{$budidaya->id}");

    $response->assertStatus(200);
    expect($response->json('data.hss'))->toBe(0);
});

test('hss is calculated correctly for 30 days old budidaya', function () {
    $budidaya = Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'tanggal_semai' => now()->subDays(30),
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/budidaya/{$budidaya->id}");

    $response->assertStatus(200);
    expect($response->json('data.hss'))->toBe(30);
});

// Meja Status Update Tests
test('meja status becomes terisi when budidaya berjalan is created', function () {
    expect($this->meja->fresh()->status)->toBe('kosong');

    $this->actingAs($this->pemilik)
        ->postJson('/api/v1/budidaya', [
            'meja_id' => $this->meja->id,
            'tanggal_semai' => now()->subDays(5)->format('Y-m-d'),
            'jumlah_tanaman' => 50,
            'status' => 'berjalan',
        ]);

    expect($this->meja->fresh()->status)->toBe('terisi');
});

test('meja status remains kosong when budidaya selesai is created', function () {
    expect($this->meja->fresh()->status)->toBe('kosong');

    $this->actingAs($this->pemilik)
        ->postJson('/api/v1/budidaya', [
            'meja_id' => $this->meja->id,
            'tanggal_semai' => now()->subDays(5)->format('Y-m-d'),
            'jumlah_tanaman' => 50,
            'status' => 'selesai',
        ]);

    expect($this->meja->fresh()->status)->toBe('kosong');
});

test('meja status becomes kosong when last budidaya berjalan changed to selesai', function () {
    $budidaya = Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'status' => 'berjalan',
    ]);

    expect($this->meja->fresh()->status)->toBe('kosong'); // Not automatically updated by factory

    // Trigger update by posting/updating
    $this->actingAs($this->pemilik)
        ->postJson('/api/v1/budidaya', [
            'meja_id' => $this->meja->id,
            'tanggal_semai' => now()->subDays(3)->format('Y-m-d'),
            'jumlah_tanaman' => 30,
            'status' => 'berjalan',
        ]);

    expect($this->meja->fresh()->status)->toBe('terisi');

    // Update the budidaya to selesai
    $this->actingAs($this->pemilik)
        ->putJson("/api/v1/budidaya/{$budidaya->id}", [
            'meja_id' => $this->meja->id,
            'tanggal_semai' => $budidaya->tanggal_semai->format('Y-m-d'),
            'jumlah_tanaman' => $budidaya->jumlah_tanaman,
            'status' => 'selesai',
        ]);

    // Should still be terisi because we created another berjalan
    expect($this->meja->fresh()->status)->toBe('terisi');

    // Get the second budidaya
    $budidaya2 = Budidaya::where('id', '!=', $budidaya->id)->first();

    // Update second to selesai
    $this->actingAs($this->pemilik)
        ->putJson("/api/v1/budidaya/{$budidaya2->id}", [
            'meja_id' => $this->meja->id,
            'tanggal_semai' => $budidaya2->tanggal_semai->format('Y-m-d'),
            'jumlah_tanaman' => $budidaya2->jumlah_tanaman,
            'status' => 'selesai',
        ]);

    // Now should be kosong
    expect($this->meja->fresh()->status)->toBe('kosong');
});

test('meja status updates correctly when budidaya moved to different meja', function () {
    $meja2 = MejaTanam::factory()->create([
        'nomor_meja' => 2,
        'kapasitas_lubang' => 120,
        'status' => 'kosong',
    ]);

    $budidaya = Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'status' => 'berjalan',
    ]);

    // Create and mark as berjalan
    $this->actingAs($this->pemilik)
        ->postJson('/api/v1/budidaya', [
            'meja_id' => $this->meja->id,
            'tanggal_semai' => now()->subDays(3)->format('Y-m-d'),
            'jumlah_tanaman' => 30,
            'status' => 'berjalan',
        ]);

    expect($this->meja->fresh()->status)->toBe('terisi');
    expect($meja2->fresh()->status)->toBe('kosong');

    // Move budidaya to meja2
    $this->actingAs($this->pemilik)
        ->putJson("/api/v1/budidaya/{$budidaya->id}", [
            'meja_id' => $meja2->id,
            'tanggal_semai' => $budidaya->tanggal_semai->format('Y-m-d'),
            'jumlah_tanaman' => $budidaya->jumlah_tanaman,
            'status' => 'berjalan',
        ]);

    expect($this->meja->fresh()->status)->toBe('terisi'); // Still has one berjalan
    expect($meja2->fresh()->status)->toBe('terisi'); // Now has berjalan
});

// Validation Tests
test('validation error when jumlah_tanaman exceeds kapasitas_lubang', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/budidaya', [
            'meja_id' => $this->meja->id,
            'tanggal_semai' => now()->subDays(5)->format('Y-m-d'),
            'jumlah_tanaman' => 150, // Exceeds 100
            'status' => 'berjalan',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['jumlah_tanaman']);
});

test('validation error when tanggal_semai is in future', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/budidaya', [
            'meja_id' => $this->meja->id,
            'tanggal_semai' => now()->addDays(1)->format('Y-m-d'),
            'jumlah_tanaman' => 50,
            'status' => 'berjalan',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tanggal_semai']);
});

test('validation error when meja_id does not exist', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/budidaya', [
            'meja_id' => 9999,
            'tanggal_semai' => now()->subDays(5)->format('Y-m-d'),
            'jumlah_tanaman' => 50,
            'status' => 'berjalan',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['meja_id']);
});

test('validation error when status is invalid', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/budidaya', [
            'meja_id' => $this->meja->id,
            'tanggal_semai' => now()->subDays(5)->format('Y-m-d'),
            'jumlah_tanaman' => 50,
            'status' => 'invalid',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('validation error when required fields are missing', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/budidaya', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['meja_id', 'tanggal_semai', 'jumlah_tanaman', 'status']);
});

// Filter Tests
test('filter budidaya by meja_id', function () {
    $meja2 = MejaTanam::factory()->create();

    Budidaya::factory()->count(3)->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
    ]);

    Budidaya::factory()->count(2)->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $meja2->id,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/budidaya?meja_id={$this->meja->id}");

    $response->assertStatus(200);
    expect($response->json('data.total'))->toBe(3);
});

test('filter budidaya by status', function () {
    Budidaya::factory()->count(3)->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'status' => 'berjalan',
    ]);

    Budidaya::factory()->count(2)->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'status' => 'selesai',
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/budidaya?status=berjalan');

    $response->assertStatus(200);
    expect($response->json('data.total'))->toBe(3);
});

test('filter budidaya by date range from', function () {
    Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'tanggal_semai' => now()->subDays(20),
    ]);

    Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'tanggal_semai' => now()->subDays(5),
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/budidaya?from=' . now()->subDays(10)->format('Y-m-d'));

    $response->assertStatus(200);
    expect($response->json('data.total'))->toBe(1);
});

test('filter budidaya by date range to', function () {
    Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'tanggal_semai' => now()->subDays(20),
    ]);

    Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'tanggal_semai' => now()->subDays(5),
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/budidaya?to=' . now()->subDays(10)->format('Y-m-d'));

    $response->assertStatus(200);
    expect($response->json('data.total'))->toBe(1);
});

test('filter budidaya by date range from and to', function () {
    Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'tanggal_semai' => now()->subDays(30),
    ]);

    Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'tanggal_semai' => now()->subDays(15),
    ]);

    Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'tanggal_semai' => now()->subDays(5),
    ]);

    $from = now()->subDays(20)->format('Y-m-d');
    $to = now()->subDays(10)->format('Y-m-d');

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/budidaya?from={$from}&to={$to}");

    $response->assertStatus(200);
    expect($response->json('data.total'))->toBe(1);
});

test('filter budidaya by multiple filters', function () {
    $meja2 = MejaTanam::factory()->create();

    Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'status' => 'berjalan',
        'tanggal_semai' => now()->subDays(10),
    ]);

    Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'status' => 'selesai',
        'tanggal_semai' => now()->subDays(10),
    ]);

    Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $meja2->id,
        'status' => 'berjalan',
        'tanggal_semai' => now()->subDays(10),
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/budidaya?meja_id={$this->meja->id}&status=berjalan");

    $response->assertStatus(200);
    expect($response->json('data.total'))->toBe(1);
});

// Resource Structure Tests
test('budidaya response includes correct structure', function () {
    $budidaya = Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/budidaya/{$budidaya->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'user_id',
                'meja_id',
                'tanggal_semai',
                'jumlah_tanaman',
                'status',
                'hss',
                'user' => ['id', 'nama', 'username', 'role'],
                'meja' => ['id', 'nomor_meja', 'kapasitas_lubang', 'status'],
                'created_at',
                'updated_at',
            ],
        ]);
});

test('budidaya list response includes pagination', function () {
    Budidaya::factory()->count(5)->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/budidaya');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data' => [
                    '*' => ['id', 'user_id', 'meja_id', 'tanggal_semai', 'jumlah_tanaman', 'status', 'hss'],
                ],
                'current_page',
                'per_page',
                'total',
            ],
        ]);
});

test('user_id is auto-filled from authenticated user', function () {
    $response = $this->actingAs($this->pegawai)
        ->postJson('/api/v1/budidaya', [
            'meja_id' => $this->meja->id,
            'tanggal_semai' => now()->subDays(5)->format('Y-m-d'),
            'jumlah_tanaman' => 50,
            'status' => 'berjalan',
        ]);

    $response->assertStatus(201);
    expect($response->json('data.user_id'))->toBe($this->pegawai->id);

    $this->assertDatabaseHas('budidayas', [
        'user_id' => $this->pegawai->id,
        'meja_id' => $this->meja->id,
    ]);
});

test('budidaya list supports custom pagination', function () {
    Budidaya::factory()->count(20)->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/budidaya?per_page=10');

    $response->assertStatus(200);
    expect($response->json('data.per_page'))->toBe(10);
    expect(count($response->json('data.data')))->toBe(10);
});

// Eager Loading Test (N+1 prevention)
test('budidaya list eager loads relations to prevent N+1', function () {
    Budidaya::factory()->count(3)->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
    ]);

    \DB::enableQueryLog();

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/budidaya');

    $queries = \DB::getQueryLog();
    \DB::disableQueryLog();

    $response->assertStatus(200);

    // Should have: 1 for budidaya + 1 for users + 1 for meja_tanams + 1 for count = 4 queries max
    // (actual count may vary with pagination, but should not be 3*2+1 = 7 or more for 3 records)
    expect(count($queries))->toBeLessThanOrEqual(5);
});

