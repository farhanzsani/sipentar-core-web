<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'username' => 'testuser',
        'password' => 'password123', // Model will hash this via 'hashed' cast
        'role' => UserRole::PEGAWAI,
    ]);
});

test('user can login with valid credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'testuser',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'nama', 'username', 'role'],
                'token',
                'token_type',
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'token_type' => 'Bearer',
            ],
        ]);
});

test('login fails with invalid password', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'testuser',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
        ]);
});

test('login fails with non-existent username', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'nobody',
        'password' => 'password123',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
        ]);
});

test('login returns validation error when username is missing', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonValidationErrors(['username']);
});

test('login returns validation error when password is missing', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'testuser',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonValidationErrors(['password']);
});

test('login returns validation error when password is too short', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'testuser',
        'password' => 'short',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('admin can login', function () {
    $admin = User::factory()->admin()->create([
        'username' => 'adminuser',
        'password' => 'password123', // Model will hash this via 'hashed' cast
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'adminuser',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'user' => ['role' => 'pemilik'],
            ],
        ]);
});
