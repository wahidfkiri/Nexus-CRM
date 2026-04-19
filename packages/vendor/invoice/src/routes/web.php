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
        Route::get('/{invoice}',       [InvoiceController::class, 'show']       )->whereNumber('invoice')->name('show');
        Route::get('/{invoice}/edit',  [InvoiceController::class, 'edit']       )->whereNumber('invoice')->name('edit');
        Route::put('/{invoice}',       [InvoiceController::class, 'update']     )->whereNumber('invoice')->name('update');
        Route::delete('/{invoice}',    [InvoiceController::class, 'destroy']    )->whereNumber('invoice')->name('destroy');

        // Actions métier
        Route::post('/{invoice}/send',      [InvoiceController::class, 'send']     )->whereNumber('invoice')->name('send');
        Route::post('/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->whereNumber('invoice')->name('duplicate');

        // Paiements
        Route::post('/{invoice}/payments',          [InvoiceController::class, 'addPayment']   )->whereNumber('invoice')->name('payments.store');
        Route::delete('/payments/{payment}',        [InvoiceController::class, 'deletePayment'])->whereNumber('payment')->name('payments.destroy');

        // AJAX
        Route::get('/data/table',   [InvoiceController::class, 'getData'] )->name('data');
        Route::get('/data/stats',   [InvoiceController::class, 'getStats'])->name('stats');
        Route::post('/bulk/delete', [InvoiceController::class, 'bulkDelete'])->name('bulk.delete');
        Route::post('/bulk/send',   [InvoiceController::class, 'bulkSend'])->name('bulk.send');

        // Exports / Import
        Route::get('/export/csv',   [InvoiceController::class, 'exportCsv']  )->name('export.csv');
        Route::get('/export/excel', [InvoiceController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf',   [InvoiceController::class, 'exportPdf']  )->name('export.pdf');
        Route::get('/{invoice}/pdf',[InvoiceController::class, 'downloadPdf'])->whereNumber('invoice')->name('pdf');
        Route::post('/import',      [InvoiceController::class, 'import']     )->name('import');

        /* ─── DEVIS (sous /invoices/quotes) ────────────────────────────── */
        Route::prefix('quotes')->name('quotes.')->group(function () {
            Route::get('/',               [InvoiceController::class, 'quotesIndex']  )->name('index');
            Route::get('/create',         [InvoiceController::class, 'quotesCreate'] )->name('create');
            Route::post('/',              [InvoiceController::class, 'quotesStore']  )->name('store');
            Route::get('/{quote}',        [InvoiceController::class, 'quotesShow']   )->whereNumber('quote')->name('show');
            Route::get('/{quote}/edit',   [InvoiceController::class, 'quotesEdit']   )->whereNumber('quote')->name('edit');
            Route::put('/{quote}',        [InvoiceController::class, 'quotesUpdate'] )->whereNumber('quote')->name('update');
            Route::delete('/{quote}',     [InvoiceController::class, 'quotesDestroy'])->whereNumber('quote')->name('destroy');
            Route::post('/{quote}/convert',[InvoiceController::class, 'quotesConvert'])->whereNumber('quote')->name('convert');
            Route::get('/{quote}/pdf',    [InvoiceController::class, 'quotesDownloadPdf'])->whereNumber('quote')->name('pdf');
            Route::get('/data/table',     [InvoiceController::class, 'quotesGetData'])->name('data');
            Route::get('/export/csv',     [InvoiceController::class, 'quotesExportCsv'])->name('export.csv');
            Route::get('/export/excel',   [InvoiceController::class, 'quotesExportExcel'])->name('export.excel');
        });

        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/',           [InvoiceController::class, 'paymentsIndex'])->name('index');
            Route::get('/data/table', [InvoiceController::class, 'paymentsData'])->name('data');
            Route::get('/data/stats', [InvoiceController::class, 'paymentsStats'])->name('stats');
            Route::get('/export/csv', [InvoiceController::class, 'paymentsExportCsv'])->name('export.csv');
            Route::get('/export/excel',[InvoiceController::class, 'paymentsExportExcel'])->name('export.excel');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/',               [InvoiceController::class, 'reportsIndex'])->name('index');
            Route::get('/export/{format}',[InvoiceController::class, 'reportsExport'])->name('export');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [InvoiceController::class, 'settingsIndex'])->name('index');
            Route::put('/', [InvoiceController::class, 'settingsUpdate'])->name('update');
        });

        // Devise AJAX
        Route::get('/currencies/rate',  [InvoiceController::class, 'getExchangeRate'])->name('currencies.rate');
    });
});
