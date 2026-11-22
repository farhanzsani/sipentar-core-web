<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\KaryawanController;
use App\Http\Controllers\Api\V1\ProfilController;
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

        // Profil endpoints - pemilik only
        Route::middleware('role:pemilik')->group(function () {
            Route::get('/profil', [ProfilController::class, 'show']);
            Route::put('/profil', [ProfilController::class, 'update']);

            // Karyawan endpoints - pemilik only
            Route::get('/karyawan', [KaryawanController::class, 'index']);
            Route::post('/karyawan', [KaryawanController::class, 'store']);
            Route::get('/karyawan/{karyawan}', [KaryawanController::class, 'show']);
            Route::put('/karyawan/{karyawan}', [KaryawanController::class, 'update']);
        });
    });
});
