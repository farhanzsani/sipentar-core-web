<?php

use App\Models\PenggunaanOperasional;
use App\Models\Stok;
use App\Models\User;

uses()->group('delete');

test('can delete stok when no penggunaan_operasional records', function () {
    $user = User::factory()->create();
    $stok = Stok::factory()->create();

    $response = $this
        ->actingAs($user)
        ->deleteJson("/api/v1/stok/{$stok->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Stok berhasil dihapus.',
        ]);

    $this->assertDatabaseMissing('stoks', ['id' => $stok->id]);
});

test('cannot delete stok that has penggunaan_operasional records - returns 409', function () {
    $user = User::factory()->create();
    $stok = Stok::factory()->create();
    
    // Create penggunaan operasional record for this stok
    PenggunaanOperasional::factory()->create([
        'stok_id' => $stok->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->deleteJson("/api/v1/stok/{$stok->id}");

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'message' => 'Stok tidak dapat dihapus karena sudah ada riwayat penggunaan operasional.',
        ]);

    $this->assertDatabaseHas('stoks', ['id' => $stok->id]);
});

test('returns 404 when stok not found', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->deleteJson('/api/v1/stok/99999');

    $response->assertStatus(404);
});

test('returns 401 when unauthenticated', function () {
    $stok = Stok::factory()->create();

    $response = $this->deleteJson("/api/v1/stok/{$stok->id}");

    $response->assertStatus(401);
});

test('both pemilik and pegawai can delete stok', function () {
    $pemilik = User::factory()->admin()->create();
    $pegawai = User::factory()->create();
    
    $stok1 = Stok::factory()->create();
    $stok2 = Stok::factory()->create();

    // Pemilik can delete
    $response1 = $this
        ->actingAs($pemilik)
        ->deleteJson("/api/v1/stok/{$stok1->id}");

    $response1->assertStatus(200);
    $this->assertDatabaseMissing('stoks', ['id' => $stok1->id]);

    // Pegawai can delete
    $response2 = $this
        ->actingAs($pegawai)
        ->deleteJson("/api/v1/stok/{$stok2->id}");

    $response2->assertStatus(200);
    $this->assertDatabaseMissing('stoks', ['id' => $stok2->id]);
});
