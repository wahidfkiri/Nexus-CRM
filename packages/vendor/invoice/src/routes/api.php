<?php

use Illuminate\Support\Facades\Route;
use Vendor\Invoice\Http\Controllers\Api\InvoiceApiController;

Route::middleware(['api', 'auth:sanctum'])->prefix('api/v1')->name('api.')->group(function () {

    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/',                  [InvoiceApiController::class, 'index']       )->name('index');
        Route::post('/',                 [InvoiceApiController::class, 'store']       )->name('store');
        Route::get('/{invoice}',         [InvoiceApiController::class, 'show']        )->name('show');
        Route::put('/{invoice}',         [InvoiceApiController::class, 'update']      )->name('update');
        Route::delete('/{invoice}',      [InvoiceApiController::class, 'destroy']     )->name('destroy');
        Route::post('/{invoice}/send',   [InvoiceApiController::class, 'send']        )->name('send');
        Route::post('/{invoice}/payments',[InvoiceApiController::class, 'addPayment'] )->name('payments.store');
        Route::get('/stats',             [InvoiceApiController::class, 'stats']       )->name('stats');
    });

    Route::prefix('quotes')->name('quotes.')->group(function () {
        Route::get('/',               [InvoiceApiController::class, 'quotesIndex']  )->name('index');
        Route::post('/',              [InvoiceApiController::class, 'quotesStore']  )->name('store');
        Route::get('/{quote}',        [InvoiceApiController::class, 'quotesShow']   )->name('show');
        Route::put('/{quote}',        [InvoiceApiController::class, 'quotesUpdate'] )->name('update');
        Route::delete('/{quote}',     [InvoiceApiController::class, 'quotesDestroy'])->name('destroy');
        Route::post('/{quote}/convert',[InvoiceApiController::class, 'quotesConvert'])->name('convert');
    });
});
