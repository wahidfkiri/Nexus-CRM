<?php

use Illuminate\Support\Facades\Route;
use Vendor\User\Http\Controllers\Api\UserApiController;

Route::middleware(['api', 'auth:sanctum', 'tenant'])->prefix('api/v1/users')->name('api.users.')->group(function () {
    Route::get('/',                  [UserApiController::class, 'index']  )->name('index');
    Route::get('/stats',             [UserApiController::class, 'stats']  )->name('stats');
    Route::get('/{user}',            [UserApiController::class, 'show']   )->name('show');
    Route::put('/{user}',            [UserApiController::class, 'update'] )->name('update');
    Route::delete('/{user}',         [UserApiController::class, 'destroy'])->name('destroy');
    Route::post('/invite',           [UserApiController::class, 'invite'] )->name('invite');
});