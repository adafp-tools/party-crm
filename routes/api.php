<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HeartbeatController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PhotoController;
use App\Http\Controllers\Api\V1\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public auth endpoints
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    // Protected endpoints
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [MeController::class, 'show'])->name('me');
        Route::post('sync/pull', [SyncController::class, 'pull'])->name('sync.pull');
        Route::post('sync/push', [SyncController::class, 'push'])->name('sync.push');
        Route::post('devices/heartbeat', [HeartbeatController::class, 'store'])->name('devices.heartbeat');
        Route::post('photos', [PhotoController::class, 'store'])->name('photos.store');
    });
});
