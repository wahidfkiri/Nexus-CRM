<?php

use Illuminate\Support\Facades\Route;
use Vendor\Client\Http\Controllers\ClientController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:clients'])->prefix('clients')->name('clients.')->group(function () {

    // ---- CRUD standard ----
    Route::get('/',             [ClientController::class, 'index']  )->name('index');
    Route::get('/create',       [ClientController::class, 'create'] )->name('create');
    Route::post('/',            [ClientController::class, 'store']  )->name('store');
    Route::get('/{client}',     [ClientController::class, 'show']   )->name('show');
    Route::get('/{client}/edit',[ClientController::class, 'edit']   )->name('edit');
    Route::put('/{client}',     [ClientController::class, 'update'] )->name('update');
    Route::delete('/{client}',  [ClientController::class, 'destroy'])->name('destroy');

    // ---- AJAX data & stats ----
    Route::get('/data/table',   [ClientController::class, 'getData']  )->name('data');
    Route::get('/data/stats',   [ClientController::class, 'getStats'] )->name('stats');
    Route::get('/data/search',  [ClientController::class, 'search']   )->name('search');

    // ---- Bulk operations ----
    Route::post('/bulk/delete', [ClientController::class, 'bulkDelete'])->name('bulk.delete');
    Route::post('/bulk/status', [ClientController::class, 'bulkStatus'])->name('bulk.status');

    // ---- Exports ----
    Route::get('/export/csv',   [ClientController::class, 'exportCsv']  )->name('export.csv');
    Route::get('/export/excel', [ClientController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/pdf',   [ClientController::class, 'exportPdf']  )->name('export.pdf');

    // ---- Import ----
    Route::post('/import',           [ClientController::class, 'import']          )->name('import');
    Route::get('/import/template',   [ClientController::class, 'downloadTemplate'])->name('import.template');
});
