<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('authenticated user can logout', function () {
    $token = $this->user->createToken('auth_token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->postJson('/api/v1/auth/logout');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
});

test('logout revokes the current token', function () {
    $token = $this->user->createToken('auth_token')->plainTextToken;

    $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->postJson('/api/v1/auth/logout');

    // Try to access protected route with revoked token
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->postJson('/api/v1/auth/logout');

    $response->assertStatus(401);
});

test('unauthenticated user cannot logout', function () {
    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
        ]);
});

test('logout with invalid token returns 401', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer invalid-token-here',
    ])->postJson('/api/v1/auth/logout');

    $response->assertStatus(401);
});
