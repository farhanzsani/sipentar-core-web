<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BudidayaController;
use App\Http\Controllers\Api\V1\KaryawanController;
use App\Http\Controllers\Api\V1\KeuanganController;
use App\Http\Controllers\Api\V1\MejaTanamController;
use App\Http\Controllers\Api\V1\NotifikasiController;
use App\Http\Controllers\Api\V1\PanenController;
use App\Http\Controllers\Api\V1\PenggunaanOperasionalController;
use App\Http\Controllers\Api\V1\ProfilController;
use App\Http\Controllers\Api\V1\StokController;
use App\Http\Controllers\Api\V1\TransaksiKeuanganController;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1'); // 5 attempts per minute

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', function () {
            return new UserResource(auth()->user());
        });

        // Budidaya endpoints - all authenticated users
        Route::get('/budidaya', [BudidayaController::class, 'index']);
        Route::post('/budidaya', [BudidayaController::class, 'store']);
        Route::get('/budidaya/{id}', [BudidayaController::class, 'show']);
        Route::put('/budidaya/{id}', [BudidayaController::class, 'update']);

        // Panen endpoints - all authenticated users
        Route::get('/panen', [PanenController::class, 'index']);
        Route::post('/panen', [PanenController::class, 'store']);
        Route::get('/panen/{id}', [PanenController::class, 'show']);
        Route::put('/panen/{id}', [PanenController::class, 'update']);

        // Meja Tanam endpoints - all authenticated users
        Route::get('/meja-tanam', [MejaTanamController::class, 'index']);

        // Stok endpoints - all authenticated users
        Route::get('/stok', [StokController::class, 'index']);
        Route::post('/stok', [StokController::class, 'store']);
        Route::get('/stok/{id}', [StokController::class, 'show']);
        Route::put('/stok/{id}', [StokController::class, 'update']);

        // Penggunaan Operasional endpoints - all authenticated users
        Route::get('/penggunaan-operasional', [PenggunaanOperasionalController::class, 'index']);
        Route::post('/penggunaan-operasional', [PenggunaanOperasionalController::class, 'store']);

        // Notifikasi endpoints - all authenticated users
        Route::get('/notifikasi', [NotifikasiController::class, 'index']);

        // Profil endpoints - pemilik only
        Route::middleware('role:pemilik')->group(function () {
            Route::get('/profil', [ProfilController::class, 'show']);
            Route::put('/profil', [ProfilController::class, 'update']);

            // Karyawan endpoints - pemilik only
            Route::get('/karyawan', [KaryawanController::class, 'index']);
            Route::post('/karyawan', [KaryawanController::class, 'store']);
            Route::get('/karyawan/{karyawan}', [KaryawanController::class, 'show']);
            Route::put('/karyawan/{karyawan}', [KaryawanController::class, 'update']);

            // Transaksi Keuangan endpoints - pemilik only
            Route::get('/transaksi-keuangan', [TransaksiKeuanganController::class, 'index']);
            Route::post('/transaksi-keuangan', [TransaksiKeuanganController::class, 'store']);
            Route::get('/transaksi-keuangan/{id}', [TransaksiKeuanganController::class, 'show']);
            Route::put('/transaksi-keuangan/{id}', [TransaksiKeuanganController::class, 'update']);

            // Keuangan endpoints - pemilik only
            Route::prefix('keuangan')->group(function () {
                Route::get('/hpp', [KeuanganController::class, 'hpp']);
                Route::get('/alokasi-kas', [KeuanganController::class, 'alokasiKas']);
                Route::get('/laporan', [KeuanganController::class, 'laporan']);
            });
        });
    });
});
