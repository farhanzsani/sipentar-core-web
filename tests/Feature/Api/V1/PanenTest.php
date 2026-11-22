<?php

use App\Enums\UserRole;
use App\Models\Budidaya;
use App\Models\MejaTanam;
use App\Models\Panen;
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

    $this->budidaya = Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $this->meja->id,
        'tanggal_semai' => now()->subDays(30),
        'jumlah_tanaman' => 50,
        'status' => 'berjalan',
    ]);
});

// Access Control Tests
test('pemilik can access panen list', function () {
    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/panen');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data panen berhasil diambil.',
        ]);
});

test('pegawai can access panen list', function () {
    $response = $this->actingAs($this->pegawai)
        ->getJson('/api/v1/panen');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data panen berhasil diambil.',
        ]);
});

test('pemilik can create panen', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->format('Y-m-d'),
            'berat_kg' => 10.5,
            'status' => 'selesai',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Panen berhasil dibuat.',
        ]);
});

test('pegawai can create panen', function () {
    $response = $this->actingAs($this->pegawai)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->format('Y-m-d'),
            'berat_kg' => 8.3,
            'status' => 'selesai',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Panen berhasil dibuat.',
        ]);
});

test('pemilik can view single panen', function () {
    $panen = Panen::factory()->create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->format('Y-m-d'),
        'berat_kg' => 10.5,
        'status' => 'selesai',
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/panen/{$panen->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data panen berhasil diambil.',
        ]);
});

test('pegawai can view single panen', function () {
    $panen = Panen::factory()->create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->format('Y-m-d'),
        'berat_kg' => 8.3,
        'status' => 'selesai',
    ]);

    $response = $this->actingAs($this->pegawai)
        ->getJson("/api/v1/panen/{$panen->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data panen berhasil diambil.',
        ]);
});

test('pemilik can update panen', function () {
    $panen = Panen::factory()->create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->format('Y-m-d'),
        'berat_kg' => null,
        'status' => 'terjadwal',
    ]);

    $response = $this->actingAs($this->pemilik)
        ->putJson("/api/v1/panen/{$panen->id}", [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->format('Y-m-d'),
            'berat_kg' => 12.5,
            'status' => 'selesai',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Panen berhasil diperbarui.',
        ]);
});

test('pegawai can update panen', function () {
    $panen = Panen::factory()->create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->format('Y-m-d'),
        'berat_kg' => null,
        'status' => 'terjadwal',
    ]);

    $response = $this->actingAs($this->pegawai)
        ->putJson("/api/v1/panen/{$panen->id}", [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->format('Y-m-d'),
            'berat_kg' => 9.8,
            'status' => 'selesai',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Panen berhasil diperbarui.',
        ]);
});

test('unauthenticated user cannot access panen endpoints', function () {
    $this->getJson('/api/v1/panen')->assertStatus(401);
    $this->postJson('/api/v1/panen')->assertStatus(401);
    $this->getJson('/api/v1/panen/1')->assertStatus(401);
    $this->putJson('/api/v1/panen/1')->assertStatus(401);
});

// Status and berat_kg Validation Tests
test('create panen with status terjadwal and null berat_kg succeeds', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->format('Y-m-d'),
            'berat_kg' => null,
            'status' => 'terjadwal',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Panen berhasil dibuat.',
        ]);

    $this->assertDatabaseHas('panens', [
        'budidaya_id' => $this->budidaya->id,
        'status' => 'terjadwal',
        'berat_kg' => null,
    ]);
});

test('create panen with status terjadwal without berat_kg field succeeds', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->format('Y-m-d'),
            'status' => 'terjadwal',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Panen berhasil dibuat.',
        ]);

    $this->assertDatabaseHas('panens', [
        'budidaya_id' => $this->budidaya->id,
        'status' => 'terjadwal',
        'berat_kg' => null,
    ]);
});

test('create panen with status selesai requires berat_kg', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->format('Y-m-d'),
            'berat_kg' => null,
            'status' => 'selesai',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['berat_kg']);
});

test('create panen with status selesai without berat_kg field fails', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->format('Y-m-d'),
            'status' => 'selesai',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['berat_kg']);
});

test('create panen with status selesai and berat_kg succeeds', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->format('Y-m-d'),
            'berat_kg' => 10.5,
            'status' => 'selesai',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Panen berhasil dibuat.',
        ]);

    $this->assertDatabaseHas('panens', [
        'budidaya_id' => $this->budidaya->id,
        'status' => 'selesai',
        'berat_kg' => 10.5,
    ]);
});

test('update panen from terjadwal to selesai requires berat_kg', function () {
    $panen = Panen::factory()->create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->format('Y-m-d'),
        'berat_kg' => null,
        'status' => 'terjadwal',
    ]);

    $response = $this->actingAs($this->pemilik)
        ->putJson("/api/v1/panen/{$panen->id}", [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->format('Y-m-d'),
            'berat_kg' => null,
            'status' => 'selesai',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['berat_kg']);
});

test('update panen from terjadwal to selesai with berat_kg succeeds', function () {
    $panen = Panen::factory()->create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->format('Y-m-d'),
        'berat_kg' => null,
        'status' => 'terjadwal',
    ]);

    $response = $this->actingAs($this->pemilik)
        ->putJson("/api/v1/panen/{$panen->id}", [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->format('Y-m-d'),
            'berat_kg' => 12.5,
            'status' => 'selesai',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Panen berhasil diperbarui.',
        ]);

    $this->assertDatabaseHas('panens', [
        'id' => $panen->id,
        'status' => 'selesai',
        'berat_kg' => 12.5,
    ]);
});

// Uniqueness Validation Tests
test('validation error when two panen have same meja_id and tanggal_panen', function () {
    // Create first panen
    Panen::factory()->create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => '2024-01-15',
        'berat_kg' => 10.5,
        'status' => 'selesai',
    ]);

    // Try to create second panen with same meja (via budidaya) and date
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => '2024-01-15',
            'berat_kg' => 8.0,
            'status' => 'selesai',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tanggal_panen']);
});

test('validation error when two panen on different budidayas but same meja and date', function () {
    $budidaya2 = Budidaya::factory()->create([
        'user_id' => $this->pegawai->id,
        'meja_id' => $this->meja->id, // Same meja
        'tanggal_semai' => now()->subDays(25),
        'jumlah_tanaman' => 40,
        'status' => 'berjalan',
    ]);

    // Create first panen for budidaya 1
    Panen::factory()->create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => '2024-01-20',
        'berat_kg' => 10.5,
        'status' => 'selesai',
    ]);

    // Try to create second panen for budidaya 2 (same meja, same date)
    $response = $this->actingAs($this->pegawai)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $budidaya2->id,
            'tanggal_panen' => '2024-01-20',
            'berat_kg' => 8.0,
            'status' => 'selesai',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tanggal_panen']);
});

test('can create panen on different meja with same date', function () {
    $meja2 = MejaTanam::factory()->create([
        'nomor_meja' => 2,
        'kapasitas_lubang' => 100,
        'status' => 'kosong',
    ]);

    $budidaya2 = Budidaya::factory()->create([
        'user_id' => $this->pegawai->id,
        'meja_id' => $meja2->id, // Different meja
        'tanggal_semai' => now()->subDays(25),
        'jumlah_tanaman' => 40,
        'status' => 'berjalan',
    ]);

    $tanggalPanen = now()->format('Y-m-d');

    // Create first panen for budidaya 1 (meja 1)
    Panen::factory()->create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => $tanggalPanen,
        'berat_kg' => 10.5,
        'status' => 'selesai',
    ]);

    // Create second panen for budidaya 2 (meja 2, same date) - should succeed
    $response = $this->actingAs($this->pegawai)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $budidaya2->id,
            'tanggal_panen' => $tanggalPanen,
            'berat_kg' => 8.0,
            'status' => 'selesai',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Panen berhasil dibuat.',
        ]);
});

test('update panen does not conflict with itself on same meja and date', function () {
    $tanggalPanen = now()->format('Y-m-d');
    
    $panen = Panen::factory()->create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => $tanggalPanen,
        'berat_kg' => 10.5,
        'status' => 'selesai',
    ]);

    // Update with same date should succeed
    $response = $this->actingAs($this->pemilik)
        ->putJson("/api/v1/panen/{$panen->id}", [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => $tanggalPanen,
            'berat_kg' => 12.0,
            'status' => 'selesai',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Panen berhasil diperbarui.',
        ]);
});

test('update panen conflicts with another panen on same meja and date', function () {
    $panen1 = Panen::factory()->create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => '2024-01-20',
        'berat_kg' => 10.5,
        'status' => 'selesai',
    ]);

    $panen2 = Panen::factory()->create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => '2024-01-25',
        'berat_kg' => 8.0,
        'status' => 'selesai',
    ]);

    // Try to update panen2 to same date as panen1
    $response = $this->actingAs($this->pemilik)
        ->putJson("/api/v1/panen/{$panen2->id}", [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => '2024-01-20', // Same as panen1
            'berat_kg' => 8.0,
            'status' => 'selesai',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tanggal_panen']);
});

// Date Validation Tests
test('validation error when tanggal_panen before budidaya tanggal_semai', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->subDays(35)->format('Y-m-d'), // Before tanggal_semai (30 days ago)
            'berat_kg' => 10.5,
            'status' => 'selesai',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tanggal_panen']);
});

test('tanggal_panen same as tanggal_semai is valid', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => $this->budidaya->tanggal_semai->format('Y-m-d'),
            'berat_kg' => 10.5,
            'status' => 'selesai',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Panen berhasil dibuat.',
        ]);
});

test('tanggal_panen after tanggal_semai is valid', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->format('Y-m-d'), // 30 days after semai
            'berat_kg' => 10.5,
            'status' => 'selesai',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Panen berhasil dibuat.',
        ]);
});

// Filter Tests
test('filter panen by budidaya_id', function () {
    $budidaya2 = Budidaya::factory()->create([
        'user_id' => $this->pegawai->id,
        'meja_id' => MejaTanam::factory()->create()->id,
        'tanggal_semai' => now()->subDays(25),
    ]);

    // Create panens with explicit valid dates
    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->format('Y-m-d'),
        'berat_kg' => 10.5,
        'status' => 'selesai',
    ]);
    
    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->subDays(1)->format('Y-m-d'),
        'berat_kg' => 11.0,
        'status' => 'selesai',
    ]);
    
    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->subDays(2)->format('Y-m-d'),
        'berat_kg' => 9.5,
        'status' => 'selesai',
    ]);

    Panen::create([
        'budidaya_id' => $budidaya2->id,
        'tanggal_panen' => now()->format('Y-m-d'),
        'berat_kg' => 8.0,
        'status' => 'selesai',
    ]);
    
    Panen::create([
        'budidaya_id' => $budidaya2->id,
        'tanggal_panen' => now()->subDays(1)->format('Y-m-d'),
        'berat_kg' => 8.5,
        'status' => 'selesai',
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/panen?budidaya_id={$this->budidaya->id}");

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(3);
});

test('filter panen by meja_id via budidaya relation', function () {
    $meja2 = MejaTanam::factory()->create();

    $budidaya2 = Budidaya::factory()->create([
        'user_id' => $this->pegawai->id,
        'meja_id' => $meja2->id,
        'tanggal_semai' => now()->subDays(25),
    ]);

    // Create panens with explicit valid dates
    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->format('Y-m-d'),
        'berat_kg' => 10.5,
        'status' => 'selesai',
    ]);
    
    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->subDays(1)->format('Y-m-d'),
        'berat_kg' => 11.0,
        'status' => 'selesai',
    ]);
    
    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->subDays(2)->format('Y-m-d'),
        'berat_kg' => 9.5,
        'status' => 'selesai',
    ]);

    Panen::create([
        'budidaya_id' => $budidaya2->id,
        'tanggal_panen' => now()->format('Y-m-d'),
        'berat_kg' => 8.0,
        'status' => 'selesai',
    ]);
    
    Panen::create([
        'budidaya_id' => $budidaya2->id,
        'tanggal_panen' => now()->subDays(1)->format('Y-m-d'),
        'berat_kg' => 8.5,
        'status' => 'selesai',
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/panen?meja_id={$this->meja->id}");

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(3);
});

test('filter panen by status', function () {
    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'status' => 'terjadwal',
        'tanggal_panen' => now()->format('Y-m-d'),
        'berat_kg' => null,
    ]);
    
    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'status' => 'terjadwal',
        'tanggal_panen' => now()->addDays(1)->format('Y-m-d'),
        'berat_kg' => null,
    ]);
    
    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'status' => 'terjadwal',
        'tanggal_panen' => now()->addDays(2)->format('Y-m-d'),
        'berat_kg' => null,
    ]);

    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'status' => 'selesai',
        'tanggal_panen' => now()->subDays(1)->format('Y-m-d'),
        'berat_kg' => 10.5,
    ]);
    
    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'status' => 'selesai',
        'tanggal_panen' => now()->subDays(2)->format('Y-m-d'),
        'berat_kg' => 11.0,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/panen?status=terjadwal');

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(3);
});

test('filter panen by date range from', function () {
    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->subDays(20)->format('Y-m-d'),
        'berat_kg' => 10.5,
        'status' => 'selesai',
    ]);

    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->subDays(5)->format('Y-m-d'),
        'berat_kg' => 11.0,
        'status' => 'selesai',
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/panen?from=' . now()->subDays(10)->format('Y-m-d'));

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(1);
});

test('filter panen by date range to', function () {
    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->subDays(20)->format('Y-m-d'),
        'berat_kg' => 10.5,
        'status' => 'selesai',
    ]);

    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->subDays(5)->format('Y-m-d'),
        'berat_kg' => 11.0,
        'status' => 'selesai',
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/panen?to=' . now()->subDays(10)->format('Y-m-d'));

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(1);
});

test('filter panen by date range from and to', function () {
    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->subDays(29)->format('Y-m-d'),
        'berat_kg' => 10.5,
        'status' => 'selesai',
    ]);

    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->subDays(15)->format('Y-m-d'),
        'berat_kg' => 11.0,
        'status' => 'selesai',
    ]);

    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->subDays(5)->format('Y-m-d'),
        'berat_kg' => 9.5,
        'status' => 'selesai',
    ]);

    $from = now()->subDays(20)->format('Y-m-d');
    $to = now()->subDays(10)->format('Y-m-d');

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/panen?from={$from}&to={$to}");

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(1);
});

test('filter panen by multiple filters', function () {
    $meja2 = MejaTanam::factory()->create();

    $budidaya2 = Budidaya::factory()->create([
        'user_id' => $this->pegawai->id,
        'meja_id' => $meja2->id,
        'tanggal_semai' => now()->subDays(25),
    ]);

    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'status' => 'selesai',
        'tanggal_panen' => now()->subDays(10)->format('Y-m-d'),
        'berat_kg' => 10.5,
    ]);

    Panen::create([
        'budidaya_id' => $this->budidaya->id,
        'status' => 'terjadwal',
        'tanggal_panen' => now()->subDays(9)->format('Y-m-d'),
        'berat_kg' => null,
    ]);

    Panen::create([
        'budidaya_id' => $budidaya2->id,
        'status' => 'selesai',
        'tanggal_panen' => now()->subDays(10)->format('Y-m-d'),
        'berat_kg' => 8.0,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/panen?meja_id={$this->meja->id}&status=selesai");

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(1);
});

// Other Validation Tests
test('validation error when budidaya_id does not exist', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => 9999,
            'tanggal_panen' => now()->format('Y-m-d'),
            'berat_kg' => 10.5,
            'status' => 'selesai',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['budidaya_id']);
});

test('validation error when status is invalid', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->format('Y-m-d'),
            'berat_kg' => 10.5,
            'status' => 'invalid',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('validation error when berat_kg is negative', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->format('Y-m-d'),
            'berat_kg' => -5.0,
            'status' => 'selesai',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['berat_kg']);
});

test('validation error when berat_kg is zero', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/panen', [
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->format('Y-m-d'),
            'berat_kg' => 0,
            'status' => 'selesai',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['berat_kg']);
});

test('validation error when required fields are missing', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/panen', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['budidaya_id', 'tanggal_panen', 'status']);
});

// Resource Structure Tests
test('panen response includes correct structure', function () {
    $panen = Panen::factory()->create([
        'budidaya_id' => $this->budidaya->id,
        'tanggal_panen' => now()->format('Y-m-d'),
        'berat_kg' => 10.5,
        'status' => 'selesai',
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/panen/{$panen->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'budidaya_id',
                'tanggal_panen',
                'berat_kg',
                'status',
                'budidaya' => [
                    'id',
                    'user_id',
                    'meja_id',
                    'tanggal_semai',
                    'jumlah_tanaman',
                    'status',
                    'hss',
                    'meja' => ['id', 'nomor_meja', 'kapasitas_lubang', 'status'],
                ],
                'created_at',
                'updated_at',
            ],
        ]);
});

test('panen list response includes pagination', function () {
    // Create panens with explicit dates to avoid validation errors
    for ($i = 0; $i < 5; $i++) {
        Panen::create([
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->subDays($i)->format('Y-m-d'),
            'berat_kg' => 10.5,
            'status' => 'selesai',
        ]);
    }

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/panen');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data' => [
                    '*' => ['id', 'budidaya_id', 'tanggal_panen', 'berat_kg', 'status'],
                ],
                'links',
                'meta',
            ],
        ]);
});

test('panen list supports custom pagination', function () {
    // Create panens with explicit dates to avoid validation errors
    for ($i = 0; $i < 20; $i++) {
        Panen::create([
            'budidaya_id' => $this->budidaya->id,
            'tanggal_panen' => now()->subDays($i)->format('Y-m-d'),
            'berat_kg' => 10.5,
            'status' => 'selesai',
        ]);
    }

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/panen?per_page=10');

    $response->assertStatus(200);
    expect($response->json('data.meta.per_page'))->toBe(10);
    expect(count($response->json('data.data')))->toBe(10);
});

// Eager Loading Test (N+1 prevention)
test('panen list eager loads relations to prevent N+1', function () {
    Panen::factory()->count(3)->create([
        'budidaya_id' => $this->budidaya->id,
    ]);

    \DB::enableQueryLog();

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/panen');

    $queries = \DB::getQueryLog();
    \DB::disableQueryLog();

    $response->assertStatus(200);

    // Should have: 1 for panen + 1 for budidayas + 1 for meja_tanams + 1 for users + 1 for count = 5 queries max
    expect(count($queries))->toBeLessThanOrEqual(6);
});
