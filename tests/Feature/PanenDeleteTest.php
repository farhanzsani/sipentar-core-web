<?php

use App\Models\Panen;
use App\Models\TransaksiKeuangan;
use App\Models\User;

uses()->group('delete');

test('can delete panen with status terjadwal and no transaksi_keuangan', function () {
    $user = User::factory()->create();
    $panen = Panen::factory()->create([
        'status' => 'terjadwal',
    ]);

    $response = $this
        ->actingAs($user)
        ->deleteJson("/api/v1/panen/{$panen->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Panen berhasil dihapus.',
        ]);

    $this->assertDatabaseMissing('panens', ['id' => $panen->id]);
});

test('cannot delete panen with status selesai - returns 409', function () {
    $user = User::factory()->create();
    $panen = Panen::factory()->create([
        'status' => 'selesai',
    ]);

    $response = $this
        ->actingAs($user)
        ->deleteJson("/api/v1/panen/{$panen->id}");

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'message' => 'Panen yang sudah selesai tidak dapat dihapus.',
        ]);

    $this->assertDatabaseHas('panens', ['id' => $panen->id]);
});

test('cannot delete panen that has transaksi_keuangan records - returns 409', function () {
    $user = User::factory()->create();
    $panen = Panen::factory()->create([
        'status' => 'terjadwal',
    ]);
    
    // Create transaksi keuangan record for this panen
    TransaksiKeuangan::factory()->create([
        'panen_id' => $panen->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->deleteJson("/api/v1/panen/{$panen->id}");

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'message' => 'Panen tidak dapat dihapus karena sudah ada transaksi keuangan terkait.',
        ]);

    $this->assertDatabaseHas('panens', ['id' => $panen->id]);
});

test('returns 404 when panen not found', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->deleteJson('/api/v1/panen/99999');

    $response->assertStatus(404);
});

test('returns 401 when unauthenticated', function () {
    $panen = Panen::factory()->create();

    $response = $this->deleteJson("/api/v1/panen/{$panen->id}");

    $response->assertStatus(401);
});

test('both pemilik and pegawai can delete panen', function () {
    $pemilik = User::factory()->admin()->create();
    $pegawai = User::factory()->create();
    
    $panen1 = Panen::factory()->create(['status' => 'terjadwal']);
    $panen2 = Panen::factory()->create(['status' => 'terjadwal']);

    // Pemilik can delete
    $response1 = $this
        ->actingAs($pemilik)
        ->deleteJson("/api/v1/panen/{$panen1->id}");

    $response1->assertStatus(200);
    $this->assertDatabaseMissing('panens', ['id' => $panen1->id]);

    // Pegawai can delete
    $response2 = $this
        ->actingAs($pegawai)
        ->deleteJson("/api/v1/panen/{$panen2->id}");

    $response2->assertStatus(200);
    $this->assertDatabaseMissing('panens', ['id' => $panen2->id]);
});
