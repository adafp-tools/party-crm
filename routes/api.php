<?php

use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\SyncController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.v1.')->group(function () {
    Route::get('me', [MeController::class, 'show'])->name('me');
    Route::post('sync/pull', [SyncController::class, 'pull'])->name('sync.pull');
});
