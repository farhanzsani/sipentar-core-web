<?php

use App\Enums\UserRole;
use App\Models\Budidaya;
use App\Models\Diagnosis;
use App\Models\MejaTanam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

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

    $meja = MejaTanam::factory()->create([
        'nomor_meja' => 1,
        'kapasitas_lubang' => 100,
        'status' => 'digunakan',
    ]);

    $this->budidaya = Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => $meja->id,
        'tanggal_semai' => now()->subDays(10),
        'jumlah_tanaman' => 50,
        'status' => 'berjalan',
    ]);
});

// Access Control Tests
test('pemilik can access diagnosis list', function () {
    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/diagnosis');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data diagnosis berhasil diambil.',
        ]);
});

test('pegawai can access diagnosis list', function () {
    $response = $this->actingAs($this->pegawai)
        ->getJson('/api/v1/diagnosis');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data diagnosis berhasil diambil.',
        ]);
});

test('unauthenticated user cannot access diagnosis list', function () {
    $response = $this->getJson('/api/v1/diagnosis');

    $response->assertStatus(401);
});

// Diagnosis Creation Tests
test('pemilik can create diagnosis with valid image', function () {
    $image = UploadedFile::fake()->image('test.jpg', 100, 100)->size(100);

    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/diagnosis', [
            'image' => $image,
            'budidaya_id' => $this->budidaya->id,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Diagnosis berhasil dibuat.',
        ])
        ->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'budidaya_id',
                'image_url',
                'hasil',
                'confidence',
                'rekomendasi',
                'tanggal_diagnosis',
                'disclaimer',
                'user',
                'budidaya',
                'created_at',
                'updated_at',
            ]
        ]);

    // Verify image was stored
    $diagnosis = Diagnosis::latest()->first();
    Storage::disk('public')->assertExists($diagnosis->image_path);

    // Verify database record
    expect($diagnosis->user_id)->toBe($this->pemilik->id);
    expect($diagnosis->budidaya_id)->toBe($this->budidaya->id);
    expect($diagnosis->hasil)->toBeIn(['sehat', 'busuk_batang', 'cacar_daun', 'busuk_akar']);
    expect($diagnosis->confidence)->toBeGreaterThanOrEqual(70.00);
    expect($diagnosis->confidence)->toBeLessThanOrEqual(99.00);
    expect($diagnosis->rekomendasi)->not->toBeEmpty();
    expect($diagnosis->tanggal_diagnosis)->not->toBeNull();
});

test('pegawai can create diagnosis with valid image', function () {
    $image = UploadedFile::fake()->image('test.png', 150, 150)->size(200);

    $response = $this->actingAs($this->pegawai)
        ->postJson('/api/v1/diagnosis', [
            'image' => $image,
            'budidaya_id' => $this->budidaya->id,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Diagnosis berhasil dibuat.',
        ]);

    $diagnosis = Diagnosis::latest()->first();
    expect($diagnosis->user_id)->toBe($this->pegawai->id);
});

test('diagnosis response includes disclaimer', function () {
    $image = UploadedFile::fake()->image('test.jpg', 100, 100)->size(100);

    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/diagnosis', [
            'image' => $image,
            'budidaya_id' => $this->budidaya->id,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.disclaimer', 'Hasil ini hanya informasi pendukung, bukan pengganti pemeriksaan langsung.');
});

// Validation Tests
test('diagnosis creation fails without image', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/diagnosis', [
            'budidaya_id' => $this->budidaya->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

test('diagnosis creation fails without budidaya_id', function () {
    $image = UploadedFile::fake()->image('test.jpg', 100, 100)->size(100);

    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/diagnosis', [
            'image' => $image,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['budidaya_id']);
});

test('diagnosis creation fails with invalid image format', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/diagnosis', [
            'image' => $file,
            'budidaya_id' => $this->budidaya->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

test('diagnosis creation fails with oversized image', function () {
    $image = UploadedFile::fake()->image('test.jpg', 2000, 2000)->size(6000); // 6MB

    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/diagnosis', [
            'image' => $image,
            'budidaya_id' => $this->budidaya->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

test('diagnosis creation fails with nonexistent budidaya_id', function () {
    $image = UploadedFile::fake()->image('test.jpg', 100, 100)->size(100);

    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/diagnosis', [
            'image' => $image,
            'budidaya_id' => 99999,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['budidaya_id']);
});

test('unauthenticated user cannot create diagnosis', function () {
    $image = UploadedFile::fake()->image('test.jpg', 100, 100)->size(100);

    $response = $this->postJson('/api/v1/diagnosis', [
        'image' => $image,
        'budidaya_id' => $this->budidaya->id,
    ]);

    $response->assertStatus(401);
});

// List and Filter Tests
test('diagnosis list returns paginated results', function () {
    Diagnosis::factory()->count(20)->create([
        'user_id' => $this->pemilik->id,
        'budidaya_id' => $this->budidaya->id,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/diagnosis');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'data',
                'links',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]
        ]);

    expect($response->json('data.meta.per_page'))->toBe(15);
});

test('diagnosis list can be filtered by budidaya_id', function () {
    $budidaya2 = Budidaya::factory()->create([
        'user_id' => $this->pemilik->id,
        'meja_id' => MejaTanam::factory()->create()->id,
    ]);

    Diagnosis::factory()->count(3)->create([
        'user_id' => $this->pemilik->id,
        'budidaya_id' => $this->budidaya->id,
    ]);

    Diagnosis::factory()->count(2)->create([
        'user_id' => $this->pemilik->id,
        'budidaya_id' => $budidaya2->id,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/diagnosis?budidaya_id=' . $this->budidaya->id);

    $response->assertStatus(200);
    expect(count($response->json('data.data')))->toBe(3);
});

test('diagnosis list can be filtered by hasil', function () {
    Diagnosis::factory()->create([
        'user_id' => $this->pemilik->id,
        'budidaya_id' => $this->budidaya->id,
        'hasil' => 'sehat',
    ]);

    Diagnosis::factory()->create([
        'user_id' => $this->pemilik->id,
        'budidaya_id' => $this->budidaya->id,
        'hasil' => 'busuk_batang',
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/diagnosis?hasil=sehat');

    $response->assertStatus(200);
    expect($response->json('data.data')[0]['hasil'])->toBe('sehat');
});

test('diagnosis list can be filtered by date range', function () {
    Diagnosis::factory()->create([
        'user_id' => $this->pemilik->id,
        'budidaya_id' => $this->budidaya->id,
        'tanggal_diagnosis' => now()->subDays(10),
    ]);

    Diagnosis::factory()->create([
        'user_id' => $this->pemilik->id,
        'budidaya_id' => $this->budidaya->id,
        'tanggal_diagnosis' => now()->subDays(2),
    ]);

    Diagnosis::factory()->create([
        'user_id' => $this->pemilik->id,
        'budidaya_id' => $this->budidaya->id,
        'tanggal_diagnosis' => now(),
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/diagnosis?from=' . now()->subDays(5)->format('Y-m-d') . '&to=' . now()->format('Y-m-d'));

    $response->assertStatus(200);
    expect(count($response->json('data.data')))->toBe(2);
});

// Detail Tests
test('pemilik can view single diagnosis', function () {
    $diagnosis = Diagnosis::factory()->create([
        'user_id' => $this->pemilik->id,
        'budidaya_id' => $this->budidaya->id,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/diagnosis/{$diagnosis->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data diagnosis berhasil diambil.',
        ])
        ->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'budidaya_id',
                'image_url',
                'hasil',
                'confidence',
                'rekomendasi',
                'tanggal_diagnosis',
                'disclaimer',
                'user',
                'budidaya',
                'created_at',
                'updated_at',
            ]
        ]);
});

test('pegawai can view single diagnosis', function () {
    $diagnosis = Diagnosis::factory()->create([
        'user_id' => $this->pegawai->id,
        'budidaya_id' => $this->budidaya->id,
    ]);

    $response = $this->actingAs($this->pegawai)
        ->getJson("/api/v1/diagnosis/{$diagnosis->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data diagnosis berhasil diambil.',
        ]);
});

test('single diagnosis response includes disclaimer', function () {
    $diagnosis = Diagnosis::factory()->create([
        'user_id' => $this->pemilik->id,
        'budidaya_id' => $this->budidaya->id,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/diagnosis/{$diagnosis->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.disclaimer', 'Hasil ini hanya informasi pendukung, bukan pengganti pemeriksaan langsung.');
});

test('viewing nonexistent diagnosis returns 404', function () {
    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/diagnosis/99999');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Diagnosis tidak ditemukan.',
        ]);
});

test('unauthenticated user cannot view diagnosis', function () {
    $diagnosis = Diagnosis::factory()->create([
        'user_id' => $this->pemilik->id,
        'budidaya_id' => $this->budidaya->id,
    ]);

    $response = $this->getJson("/api/v1/diagnosis/{$diagnosis->id}");

    $response->assertStatus(401);
});

// Confidence Value Tests
test('diagnosis confidence is formatted to 2 decimal places', function () {
    $diagnosis = Diagnosis::factory()->create([
        'user_id' => $this->pemilik->id,
        'budidaya_id' => $this->budidaya->id,
        'confidence' => 87.456,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/diagnosis/{$diagnosis->id}");

    $response->assertStatus(200);
    expect($response->json('data.confidence'))->toBe('87.46');
});

// Rekomendasi Tests
test('diagnosis has correct rekomendasi for sehat', function () {
    $diagnosis = Diagnosis::factory()->create([
        'user_id' => $this->pemilik->id,
        'budidaya_id' => $this->budidaya->id,
        'hasil' => 'sehat',
        'rekomendasi' => config('sipentar.diagnosis_recommendations.sehat'),
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/diagnosis/{$diagnosis->id}");

    $response->assertStatus(200);
    expect($response->json('data.rekomendasi'))->toContain('kondisi baik');
});

test('diagnosis has correct rekomendasi for busuk_batang', function () {
    $diagnosis = Diagnosis::factory()->create([
        'user_id' => $this->pemilik->id,
        'budidaya_id' => $this->budidaya->id,
        'hasil' => 'busuk_batang',
        'rekomendasi' => config('sipentar.diagnosis_recommendations.busuk_batang'),
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/diagnosis/{$diagnosis->id}");

    $response->assertStatus(200);
    expect($response->json('data.rekomendasi'))->toContain('busuk batang');
});
