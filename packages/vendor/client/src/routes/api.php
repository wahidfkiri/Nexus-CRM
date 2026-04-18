<?php

use Illuminate\Support\Facades\Route;
use Vendor\Client\Http\Controllers\Api\ClientApiController;

Route::middleware(['api', 'auth:sanctum', 'tenant'])->prefix('api/clients')->name('api.clients.')->group(function () {
    
    // Routes principales
    Route::get('/', [ClientApiController::class, 'index'])->name('index');
    Route::post('/', [ClientApiController::class, 'store'])->name('store');
    Route::get('/{client}', [ClientApiController::class, 'show'])->name('show');
    Route::put('/{client}', [ClientApiController::class, 'update'])->name('update');
    Route::delete('/{client}', [ClientApiController::class, 'destroy'])->name('destroy');
    
    // Routes d'export
    Route::get('/export/all', [ClientApiController::class, 'export'])->name('export');
    Route::post('/import', [ClientApiController::class, 'import'])->name('import');
    
    // Routes d'actions massives
    Route::post('/bulk-delete', [ClientApiController::class, 'bulkDelete'])->name('bulk-delete');
    Route::post('/bulk-status', [ClientApiController::class, 'bulkStatus'])->name('bulk-status');
    
    // Routes de recherche et filtres
    Route::get('/search', [ClientApiController::class, 'search'])->name('search');
    Route::get('/filter', [ClientApiController::class, 'filter'])->name('filter');
    
    // Routes statistiques
    Route::get('/stats/summary', [ClientApiController::class, 'getStats'])->name('stats');
});