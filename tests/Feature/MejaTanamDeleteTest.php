<?php

use App\Models\Budidaya;
use App\Models\MejaTanam;
use App\Models\User;

uses()->group('delete');

test('authenticated user can delete meja tanam when no budidaya exists', function () {
    $user = User::factory()->create();
    $meja = MejaTanam::factory()->create();

    $response = $this
        ->actingAs($user)
        ->deleteJson("/api/v1/meja-tanam/{$meja->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Meja tanam berhasil dihapus.',
        ]);

    $this->assertDatabaseMissing('meja_tanams', ['id' => $meja->id]);
});

test('cannot delete meja tanam with active budidaya - returns 409', function () {
    $user = User::factory()->create();
    $meja = MejaTanam::factory()->create();
    
    // Create budidaya with status berjalan
    Budidaya::factory()->create([
        'meja_id' => $meja->id,
        'status' => 'berjalan',
    ]);

    $response = $this
        ->actingAs($user)
        ->deleteJson("/api/v1/meja-tanam/{$meja->id}");

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'message' => 'Meja tanam tidak dapat dihapus karena masih ada budidaya yang berjalan.',
        ]);

    $this->assertDatabaseHas('meja_tanams', ['id' => $meja->id]);
});

test('can delete meja tanam with selesai budidaya', function () {
    $user = User::factory()->create();
    $meja = MejaTanam::factory()->create();
    
    // Create budidaya with status selesai (not blocking)
    Budidaya::factory()->create([
        'meja_id' => $meja->id,
        'status' => 'selesai',
    ]);

    $response = $this
        ->actingAs($user)
        ->deleteJson("/api/v1/meja-tanam/{$meja->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Meja tanam berhasil dihapus.',
        ]);

    $this->assertDatabaseMissing('meja_tanams', ['id' => $meja->id]);
});

test('returns 404 when meja tanam not found', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->deleteJson('/api/v1/meja-tanam/99999');

    $response->assertStatus(404);
});

test('returns 401 when unauthenticated', function () {
    $meja = MejaTanam::factory()->create();

    $response = $this->deleteJson("/api/v1/meja-tanam/{$meja->id}");

    $response->assertStatus(401);
});

test('both pemilik and pegawai can delete meja tanam', function () {
    $pemilik = User::factory()->admin()->create();
    $pegawai = User::factory()->create();
    
    $meja1 = MejaTanam::factory()->create();
    $meja2 = MejaTanam::factory()->create();

    // Pemilik can delete
    $response1 = $this
        ->actingAs($pemilik)
        ->deleteJson("/api/v1/meja-tanam/{$meja1->id}");

    $response1->assertStatus(200);
    $this->assertDatabaseMissing('meja_tanams', ['id' => $meja1->id]);

    // Pegawai can delete
    $response2 = $this
        ->actingAs($pegawai)
        ->deleteJson("/api/v1/meja-tanam/{$meja2->id}");

    $response2->assertStatus(200);
    $this->assertDatabaseMissing('meja_tanams', ['id' => $meja2->id]);
});
