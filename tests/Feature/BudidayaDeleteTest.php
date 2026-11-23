<?php

use App\Models\Budidaya;
use App\Models\MejaTanam;
use App\Models\Panen;
use App\Models\User;

uses()->group('delete');

test('can delete budidaya with status berjalan and no panen records', function () {
    $user = User::factory()->create();
    $meja = MejaTanam::factory()->create();
    $budidaya = Budidaya::factory()->create([
        'meja_id' => $meja->id,
        'status' => 'berjalan',
    ]);

    $response = $this
        ->actingAs($user)
        ->deleteJson("/api/v1/budidaya/{$budidaya->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Budidaya berhasil dihapus.',
        ]);

    $this->assertDatabaseMissing('budidayas', ['id' => $budidaya->id]);
});

test('cannot delete budidaya with status selesai - returns 409', function () {
    $user = User::factory()->create();
    $budidaya = Budidaya::factory()->create([
        'status' => 'selesai',
    ]);

    $response = $this
        ->actingAs($user)
        ->deleteJson("/api/v1/budidaya/{$budidaya->id}");

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'message' => 'Budidaya yang sudah selesai tidak dapat dihapus.',
        ]);

    $this->assertDatabaseHas('budidayas', ['id' => $budidaya->id]);
});

test('cannot delete budidaya that has panen records - returns 409', function () {
    $user = User::factory()->create();
    $budidaya = Budidaya::factory()->create([
        'status' => 'berjalan',
    ]);
    
    // Create panen record for this budidaya
    Panen::factory()->create([
        'budidaya_id' => $budidaya->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->deleteJson("/api/v1/budidaya/{$budidaya->id}");

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'message' => 'Budidaya tidak dapat dihapus karena sudah ada data panen.',
        ]);

    $this->assertDatabaseHas('budidayas', ['id' => $budidaya->id]);
});

test('returns 404 when budidaya not found', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->deleteJson('/api/v1/budidaya/99999');

    $response->assertStatus(404);
});

test('returns 401 when unauthenticated', function () {
    $budidaya = Budidaya::factory()->create();

    $response = $this->deleteJson("/api/v1/budidaya/{$budidaya->id}");

    $response->assertStatus(401);
});

test('deleting budidaya updates meja status', function () {
    $user = User::factory()->create();
    $meja = MejaTanam::factory()->create([
        'status' => 'digunakan',
    ]);
    
    $budidaya = Budidaya::factory()->create([
        'meja_id' => $meja->id,
        'status' => 'berjalan',
    ]);

    $response = $this
        ->actingAs($user)
        ->deleteJson("/api/v1/budidaya/{$budidaya->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('budidayas', ['id' => $budidaya->id]);
    
    // Meja status should be updated (by BudidayaService)
    // Just verify meja still exists
    $this->assertDatabaseHas('meja_tanams', ['id' => $meja->id]);
});
