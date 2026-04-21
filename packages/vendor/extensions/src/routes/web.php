<?php

use Illuminate\Support\Facades\Route;
use Vendor\Extensions\Http\Controllers\MarketplaceController;
use Vendor\Extensions\Http\Controllers\SuperAdmin\ExtensionAdminController;

/* ══════════════════════════════════════════════════════════════════════════
   SUPER-ADMIN — Gestion du catalogue
   ══════════════════════════════════════════════════════════════════════════ */
Route::middleware(['web', 'auth'])
    ->prefix('superadmin/extensions')
    ->name('superadmin.extensions.')
    ->group(function () {

    // CRUD catalogue
    Route::get('/',                    [ExtensionAdminController::class, 'index']        )->name('index');
    Route::get('/create',              [ExtensionAdminController::class, 'create']       )->name('create');
    Route::post('/',                   [ExtensionAdminController::class, 'store']        )->name('store');
    Route::get('/{extension}',         [ExtensionAdminController::class, 'show']         )->name('show');
    Route::get('/{extension}/edit',    [ExtensionAdminController::class, 'edit']         )->name('edit');
    Route::put('/{extension}',         [ExtensionAdminController::class, 'update']       )->name('update');
    Route::delete('/{extension}',      [ExtensionAdminController::class, 'destroy']      )->name('destroy');

    // Toggles rapides
    Route::post('/{extension}/featured', [ExtensionAdminController::class, 'toggleFeatured'])->name('featured');
    Route::post('/{extension}/status',   [ExtensionAdminController::class, 'toggleStatus']  )->name('status');

    // AJAX data
    Route::get('/data/table',          [ExtensionAdminController::class, 'getData']      )->name('data');
    Route::get('/data/stats',          [ExtensionAdminController::class, 'getStats']     )->name('stats');

    // Export
    Route::get('/export/excel',        [ExtensionAdminController::class, 'exportExcel']  )->name('export.excel');

    // Gestion des activations (tous tenants)
    Route::prefix('activations')->name('activations.')->group(function () {
        Route::get('/',                          [ExtensionAdminController::class, 'activationsIndex']  )->name('index');
        Route::get('/data',                      [ExtensionAdminController::class, 'activationsData']   )->name('data');
        Route::post('/{activation}/suspend',     [ExtensionAdminController::class, 'suspendActivation'] )->name('suspend');
        Route::post('/{activation}/restore',     [ExtensionAdminController::class, 'restoreActivation'] )->name('restore');
    });
});

/* ══════════════════════════════════════════════════════════════════════════
   TENANT — Marketplace & mes applications
   ══════════════════════════════════════════════════════════════════════════ */
Route::middleware(['web', 'auth', 'tenant'])
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {

    // Pages
    Route::get('/',                    [MarketplaceController::class, 'index']       )->name('index');
    Route::get('/my-apps',             [MarketplaceController::class, 'myApps']      )->name('my-apps');
    Route::get('/{slug}',              [MarketplaceController::class, 'show']        )->name('show');
    Route::get('/{extension}/settings',[MarketplaceController::class, 'settings']    )->name('settings');

    // Actions AJAX
    Route::post('/{extension}/activate',      [MarketplaceController::class, 'activate']    )->name('activate');
    Route::post('/{extension}/deactivate',    [MarketplaceController::class, 'deactivate']  )->name('deactivate');
    Route::post('/{extension}/settings/save', [MarketplaceController::class, 'saveSettings'])->name('settings.save');

    // Data AJAX
    Route::get('/data/apps',           [MarketplaceController::class, 'getData']     )->name('data');
    Route::get('/data/stats',          [MarketplaceController::class, 'getStats']    )->name('stats');
});