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

test('pemilik can view own profile', function () {
    $response = $this->actingAs($this->pemilik)
        ->getJson('/api/v1/profil');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Data profil berhasil diambil.',
            'data' => [
                'id' => $this->pemilik->id,
                'nama' => 'Pemilik User',
                'username' => 'pemilik',
                'role' => 'pemilik',
            ],
        ]);
});

test('pegawai cannot access profil endpoint', function () {
    $response = $this->actingAs($this->pegawai)
        ->getJson('/api/v1/profil');

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Anda tidak memiliki akses ke resource ini.',
        ]);
});

test('pemilik can update own profile without password', function () {
    $response = $this->actingAs($this->pemilik)
        ->putJson('/api/v1/profil', [
            'nama' => 'Updated Pemilik',
            'username' => 'pemilik_updated',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'nama' => 'Updated Pemilik',
                'username' => 'pemilik_updated',
                'role' => 'pemilik',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $this->pemilik->id,
        'nama' => 'Updated Pemilik',
        'username' => 'pemilik_updated',
    ]);
});

test('pemilik can update own profile with password', function () {
    $response = $this->actingAs($this->pemilik)
        ->putJson('/api/v1/profil', [
            'nama' => 'Pemilik User',
            'username' => 'pemilik',
            'password' => 'newpassword123',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
        ]);

    $this->pemilik->refresh();
    expect(Hash::check('newpassword123', $this->pemilik->password))->toBeTrue();
});

test('pegawai cannot update profil', function () {
    $response = $this->actingAs($this->pegawai)
        ->putJson('/api/v1/profil', [
            'nama' => 'Hacked Name',
            'username' => 'hacked',
        ]);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Anda tidak memiliki akses ke resource ini.',
        ]);
});

test('profil update validates nama is required', function () {
    $response = $this->actingAs($this->pemilik)
        ->putJson('/api/v1/profil', [
            'username' => 'pemilik',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['nama']);
});

test('profil update validates username is required', function () {
    $response = $this->actingAs($this->pemilik)
        ->putJson('/api/v1/profil', [
            'nama' => 'Pemilik User',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username']);
});

test('profil update validates username uniqueness', function () {
    $response = $this->actingAs($this->pemilik)
        ->putJson('/api/v1/profil', [
            'nama' => 'Pemilik User',
            'username' => 'pegawai', // Already taken by another user
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username']);
});

test('profil update allows keeping same username', function () {
    $response = $this->actingAs($this->pemilik)
        ->putJson('/api/v1/profil', [
            'nama' => 'Updated Name',
            'username' => 'pemilik', // Same as current
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
});

test('profil update validates password minimum length', function () {
    $response = $this->actingAs($this->pemilik)
        ->putJson('/api/v1/profil', [
            'nama' => 'Pemilik User',
            'username' => 'pemilik',
            'password' => 'short',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('profil update validates nama max length', function () {
    $response = $this->actingAs($this->pemilik)
        ->putJson('/api/v1/profil', [
            'nama' => str_repeat('a', 101),
            'username' => 'pemilik',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['nama']);
});

test('profil update validates username max length', function () {
    $response = $this->actingAs($this->pemilik)
        ->putJson('/api/v1/profil', [
            'nama' => 'Pemilik User',
            'username' => str_repeat('a', 51),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username']);
});

test('unauthenticated user cannot access profil', function () {
    $response = $this->getJson('/api/v1/profil');

    $response->assertStatus(401);
});
