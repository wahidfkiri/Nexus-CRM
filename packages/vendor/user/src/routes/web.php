<?php

use Illuminate\Support\Facades\Route;
use Vendor\User\Http\Controllers\UserController;

// ── Route publique : accepter une invitation ───────────────────────────────
Route::middleware(['web'])->group(function () {
    Route::get('/invitation/{token}',  [UserController::class, 'acceptForm'])  ->name('users.accept');
    Route::post('/invitation/{token}', [UserController::class, 'acceptSubmit'])->name('users.accept.submit');
});

// ── Routes protégées : gestion des utilisateurs ───────────────────────────
Route::middleware(['web', 'auth', 'tenant'])->prefix('users')->name('users.')->group(function () {

    // CRUD
    Route::get('/',                [UserController::class, 'index']  )->name('index');
    Route::get('/invite',          [UserController::class, 'create'] )->name('create');
    Route::post('/invite',         [UserController::class, 'store']  )->name('store');
    Route::get('/{user}',          [UserController::class, 'show']   )->name('show');
    Route::get('/{user}/edit',     [UserController::class, 'edit']   )->name('edit');
    Route::put('/{user}',          [UserController::class, 'update'] )->name('update');
    Route::delete('/{user}',       [UserController::class, 'destroy'])->name('destroy');

    // Actions métier
    Route::post('/{user}/suspend',  [UserController::class, 'suspend'] )->name('suspend');
    Route::post('/{user}/activate', [UserController::class, 'activate'])->name('activate');
    Route::post('/{user}/avatar',   [UserController::class, 'uploadAvatar'])->name('avatar');

    // AJAX
    Route::get('/data/table',      [UserController::class, 'getData'] )->name('data');
    Route::get('/data/stats',      [UserController::class, 'getStats'])->name('stats');

    // Bulk
    Route::post('/bulk/delete',    [UserController::class, 'bulkDelete'])->name('bulk.delete');
    Route::post('/bulk/status',    [UserController::class, 'bulkStatus'])->name('bulk.status');

    // Exports
    Route::get('/export/csv',      [UserController::class, 'exportCsv']  )->name('export.csv');
    Route::get('/export/excel',    [UserController::class, 'exportExcel'])->name('export.excel');

    // Invitations
    Route::get('/invitations/list',              [UserController::class, 'invitations']    )->name('invitations');
    Route::get('/invitations/data',              [UserController::class, 'invitationsData'])->name('invitations.data');
    Route::post('/invitations/{invitation}/resend', [UserController::class, 'resendInvitation'])->name('invitations.resend');
    Route::delete('/invitations/{invitation}',   [UserController::class, 'revokeInvitation'])->name('invitations.revoke');
});