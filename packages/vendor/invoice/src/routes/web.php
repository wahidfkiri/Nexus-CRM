<?php

use Illuminate\Support\Facades\Route;
use Vendor\Invoice\Http\Controllers\InvoiceController;

Route::middleware(['web', 'auth'])->group(function () {

    /* ─── FACTURES ─────────────────────────────────────────────────────── */
    Route::prefix('invoices')->name('invoices.')->group(function () {
        // CRUD
        Route::get('/',                [InvoiceController::class, 'index']      )->name('index');
        Route::get('/create',          [InvoiceController::class, 'create']     )->name('create');
        Route::post('/',               [InvoiceController::class, 'store']      )->name('store');
        Route::get('/{invoice}',       [InvoiceController::class, 'show']       )->name('show');
        Route::get('/{invoice}/edit',  [InvoiceController::class, 'edit']       )->name('edit');
        Route::put('/{invoice}',       [InvoiceController::class, 'update']     )->name('update');
        Route::delete('/{invoice}',    [InvoiceController::class, 'destroy']    )->name('destroy');

        // Actions métier
        Route::post('/{invoice}/send',      [InvoiceController::class, 'send']     )->name('send');
        Route::post('/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('duplicate');

        // Paiements
        Route::post('/{invoice}/payments',          [InvoiceController::class, 'addPayment']   )->name('payments.store');
        Route::delete('/payments/{payment}',        [InvoiceController::class, 'deletePayment'])->name('payments.destroy');

        // AJAX
        Route::get('/data/table',   [InvoiceController::class, 'getData'] )->name('data');
        Route::get('/data/stats',   [InvoiceController::class, 'getStats'])->name('stats');

        // Exports / Import
        Route::get('/export/csv',   [InvoiceController::class, 'exportCsv']  )->name('export.csv');
        Route::get('/export/excel', [InvoiceController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf',   [InvoiceController::class, 'exportPdf']  )->name('export.pdf');
        Route::get('/{invoice}/pdf',[InvoiceController::class, 'downloadPdf'])->name('pdf');
        Route::post('/import',      [InvoiceController::class, 'import']     )->name('import');

        /* ─── DEVIS (sous /invoices/quotes) ────────────────────────────── */
        Route::prefix('quotes')->name('quotes.')->group(function () {
            Route::get('/',               [InvoiceController::class, 'quotesIndex']  )->name('index');
            Route::get('/create',         [InvoiceController::class, 'quotesCreate'] )->name('create');
            Route::post('/',              [InvoiceController::class, 'quotesStore']  )->name('store');
            Route::get('/{quote}',        [InvoiceController::class, 'quotesShow']   )->name('show');
            Route::get('/{quote}/edit',   [InvoiceController::class, 'quotesEdit']   )->name('edit');
            Route::put('/{quote}',        [InvoiceController::class, 'quotesUpdate'] )->name('update');
            Route::delete('/{quote}',     [InvoiceController::class, 'quotesDestroy'])->name('destroy');
            Route::post('/{quote}/convert',[InvoiceController::class, 'quotesConvert'])->name('convert');
            Route::get('/{quote}/pdf',    [InvoiceController::class, 'quotesDownloadPdf'])->name('pdf');
            Route::get('/data/table',     [InvoiceController::class, 'quotesGetData'])->name('data');
        });

        // Devise AJAX
        Route::get('/currencies/rate',  [InvoiceController::class, 'getExchangeRate'])->name('currencies.rate');
    });
});
