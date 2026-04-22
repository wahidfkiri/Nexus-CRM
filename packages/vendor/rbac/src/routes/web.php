<?php

use Illuminate\Support\Facades\Route;
use Vendor\Rbac\Http\Controllers\RbacController;

Route::middleware(['web', 'auth', 'tenant', 'admin'])->prefix('rbac')->name('rbac.')->group(function () {

    /* ── Rôles ─────────────────────────────────────────────────────────── */
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/',                  [RbacController::class, 'rolesIndex']  )->name('index');
        Route::get('/create',            [RbacController::class, 'rolesCreate'] )->name('create');
        Route::post('/',                 [RbacController::class, 'rolesStore']  )->name('store');
        Route::get('/{role}',            [RbacController::class, 'rolesShow']   )->name('show');
        Route::get('/{role}/edit',       [RbacController::class, 'rolesEdit']   )->name('edit');
        Route::put('/{role}',            [RbacController::class, 'rolesUpdate'] )->name('update');
        Route::delete('/{role}',         [RbacController::class, 'rolesDestroy'])->name('destroy');

        // Sync permissions via AJAX (depuis la page show)
        Route::post('/{role}/sync-permissions', [RbacController::class, 'rolesSync'])->name('sync');

        // AJAX data
        Route::get('/data/table',        [RbacController::class, 'rolesData']   )->name('data');
        Route::get('/data/stats',        [RbacController::class, 'stats']       )->name('stats');
    });

    /* ── Permissions ────────────────────────────────────────────────────── */
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [RbacController::class, 'permissionsIndex'])->name('index');
    });

    /* ── Assigner un rôle à un utilisateur ─────────────────────────────── */
    Route::post('/users/{user}/assign-role', [RbacController::class, 'assignRole'])->name('users.assign-role');
});
