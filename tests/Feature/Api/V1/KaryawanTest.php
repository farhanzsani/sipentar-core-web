<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

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

test('pemilik can list karyawan', function () {
    // Create additional pegawai users
    User::factory()->count(3)->create([
        'role' => UserRole::PEGAWAI,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/karyawan');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data karyawan berhasil diambil.',
        ])
        ->assertJsonStructure([
            'data' => [
                'data' => [
                    '*' => ['id', 'nama', 'username', 'role'],
                ],
                'current_page',
                'per_page',
                'total',
            ],
        ]);

    // Should only show pegawai users (4 total, not the pemilik)
    expect($response->json('data.total'))->toBe(4);
});

test('karyawan list only shows pegawai role users', function () {
    // Create more pemilik and pegawai users
    User::factory()->count(2)->create(['role' => UserRole::PEMILIK]);
    User::factory()->count(3)->create(['role' => UserRole::PEGAWAI]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/karyawan');

    $response->assertStatus(200);

    // Should only return pegawai users (3 new + 1 from beforeEach = 4)
    expect($response->json('data.total'))->toBe(4);
    
    $roles = collect($response->json('data.data'))->pluck('role')->unique();
    expect($roles->all())->toBe(['pegawai']);
});

test('pegawai cannot access karyawan list', function () {
    $response = $this->actingAs($this->pegawai)
        ->getJson('/api/v1/karyawan');

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Anda tidak memiliki akses ke resource ini.',
        ]);
});

test('pemilik can create karyawan', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/karyawan', [
            'nama' => 'Karyawan Baru',
            'username' => 'karyawan_baru',
            'password' => 'password123',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Karyawan berhasil ditambahkan.',
            'data' => [
                'nama' => 'Karyawan Baru',
                'username' => 'karyawan_baru',
                'role' => 'pegawai',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'nama' => 'Karyawan Baru',
        'username' => 'karyawan_baru',
        'role' => UserRole::PEGAWAI->value,
    ]);
});

test('karyawan auto-assigned pegawai role', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/karyawan', [
            'nama' => 'New Karyawan',
            'username' => 'newkaryawan',
            'password' => 'password123',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'role' => 'pegawai',
            ],
        ]);
});

test('pegawai cannot create karyawan', function () {
    $response = $this->actingAs($this->pegawai)
        ->postJson('/api/v1/karyawan', [
            'nama' => 'Should Fail',
            'username' => 'shouldfail',
            'password' => 'password123',
        ]);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Anda tidak memiliki akses ke resource ini.',
        ]);
});

test('pemilik can view single karyawan', function () {
    $response = $this->actingAs($this->pemilik)
        ->getJson("/api/v1/karyawan/{$this->pegawai->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data karyawan berhasil diambil.',
            'data' => [
                'id' => $this->pegawai->id,
                'nama' => 'Pegawai User',
                'username' => 'pegawai',
                'role' => 'pegawai',
            ],
        ]);
});

test('pegawai cannot view karyawan details', function () {
    $otherPegawai = User::factory()->create(['role' => UserRole::PEGAWAI]);

    $response = $this->actingAs($this->pegawai)
        ->getJson("/api/v1/karyawan/{$otherPegawai->id}");

    $response->assertStatus(403);
});

test('pemilik can update karyawan without password', function () {
    $response = $this->actingAs($this->pemilik)
        ->putJson("/api/v1/karyawan/{$this->pegawai->id}", [
            'nama' => 'Updated Pegawai',
            'username' => 'pegawai_updated',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Karyawan berhasil diperbarui.',
            'data' => [
                'nama' => 'Updated Pegawai',
                'username' => 'pegawai_updated',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $this->pegawai->id,
        'nama' => 'Updated Pegawai',
        'username' => 'pegawai_updated',
    ]);
});

test('pemilik can update karyawan with password', function () {
    $oldPassword = $this->pegawai->password;

    $response = $this->actingAs($this->pemilik)
        ->putJson("/api/v1/karyawan/{$this->pegawai->id}", [
            'nama' => 'Pegawai User',
            'username' => 'pegawai',
            'password' => 'newpassword123',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Karyawan berhasil diperbarui.',
        ]);

    $this->pegawai->refresh();
    expect(Hash::check('newpassword123', $this->pegawai->password))->toBeTrue();
    expect($this->pegawai->password)->not->toBe($oldPassword);
});

test('pegawai cannot update karyawan', function () {
    $otherPegawai = User::factory()->create(['role' => UserRole::PEGAWAI]);

    $response = $this->actingAs($this->pegawai)
        ->putJson("/api/v1/karyawan/{$otherPegawai->id}", [
            'nama' => 'Hacked',
            'username' => 'hacked',
        ]);

    $response->assertStatus(403);
});

test('karyawan store validates nama is required', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/karyawan', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['nama']);
});

test('karyawan store validates username is required', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/karyawan', [
            'nama' => 'Test User',
            'password' => 'password123',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username']);
});

test('karyawan store validates password is required', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/karyawan', [
            'nama' => 'Test User',
            'username' => 'testuser',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('karyawan store validates username uniqueness', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/karyawan', [
            'nama' => 'Test User',
            'username' => 'pegawai', // Already exists
            'password' => 'password123',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username']);
});

test('karyawan store validates password minimum length', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/karyawan', [
            'nama' => 'Test User',
            'username' => 'testuser',
            'password' => 'short',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('karyawan update validates username uniqueness', function () {
    $otherPegawai = User::factory()->create([
        'username' => 'otherpegawai',
        'role' => UserRole::PEGAWAI,
    ]);

    $response = $this->actingAs($this->pemilik)
        ->putJson("/api/v1/karyawan/{$this->pegawai->id}", [
            'nama' => 'Pegawai User',
            'username' => 'otherpegawai', // Already taken
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username']);
});

test('karyawan update allows keeping same username', function () {
    $response = $this->actingAs($this->pemilik)
        ->putJson("/api/v1/karyawan/{$this->pegawai->id}", [
            'nama' => 'Updated Name',
            'username' => 'pegawai', // Same username
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
});

test('karyawan list supports pagination', function () {
    User::factory()->count(20)->create(['role' => UserRole::PEGAWAI]);

    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/karyawan?per_page=10');

    $response->assertStatus(200);
    expect($response->json('data.per_page'))->toBe(10);
    expect(count($response->json('data.data')))->toBe(10);
});

test('password is hashed when stored', function () {
    $response = $this->actingAs($this->pemilik)
        ->postJson('/api/v1/karyawan', [
            'nama' => 'Test User',
            'username' => 'testuser',
            'password' => 'password123',
        ]);

    $response->assertStatus(201);

    $user = User::where('username', 'testuser')->first();
    expect($user->password)->not->toBe('password123');
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

test('unauthenticated user cannot access karyawan endpoints', function () {
    $this->getJson('/api/v1/karyawan')->assertStatus(401);
    $this->postJson('/api/v1/karyawan')->assertStatus(401);
    $this->getJson("/api/v1/karyawan/{$this->pegawai->id}")->assertStatus(401);
    $this->putJson("/api/v1/karyawan/{$this->pegawai->id}")->assertStatus(401);
});
