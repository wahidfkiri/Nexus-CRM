<?php

use Illuminate\Support\Facades\Route;
use Vendor\Stock\Http\Controllers\StockController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:stock'])->prefix('stock')->name('stock.')->group(function () {
    Route::get('/', fn () => redirect()->route('stock.articles.index'));

    Route::prefix('articles')->name('articles.')->group(function () {
        Route::get('/', [StockController::class, 'articlesIndex'])->name('index');
        Route::get('/create', [StockController::class, 'articlesCreate'])->name('create');
        Route::post('/', [StockController::class, 'articlesStore'])->name('store');
        Route::get('/{article}', [StockController::class, 'articlesShow'])->whereNumber('article')->name('show');
        Route::get('/{article}/edit', [StockController::class, 'articlesEdit'])->whereNumber('article')->name('edit');
        Route::put('/{article}', [StockController::class, 'articlesUpdate'])->whereNumber('article')->name('update');
        Route::delete('/{article}', [StockController::class, 'articlesDestroy'])->whereNumber('article')->name('destroy');

        Route::get('/data/table', [StockController::class, 'articlesData'])->name('data');
        Route::get('/data/search', [StockController::class, 'articlesSearch'])->name('search');
        Route::get('/export/excel', [StockController::class, 'articlesExportExcel'])->name('export.excel');
        Route::post('/import', [StockController::class, 'articlesImport'])->name('import');
    });

    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/', [StockController::class, 'suppliersIndex'])->name('index');
        Route::get('/create', [StockController::class, 'suppliersCreate'])->name('create');
        Route::post('/', [StockController::class, 'suppliersStore'])->name('store');
        Route::get('/{supplier}', [StockController::class, 'suppliersShow'])->whereNumber('supplier')->name('show');
        Route::get('/{supplier}/edit', [StockController::class, 'suppliersEdit'])->whereNumber('supplier')->name('edit');
        Route::put('/{supplier}', [StockController::class, 'suppliersUpdate'])->whereNumber('supplier')->name('update');
        Route::delete('/{supplier}', [StockController::class, 'suppliersDestroy'])->whereNumber('supplier')->name('destroy');

        Route::get('/data/table', [StockController::class, 'suppliersData'])->name('data');
        Route::get('/export/excel', [StockController::class, 'suppliersExportExcel'])->name('export.excel');
    });

    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [StockController::class, 'ordersIndex'])->name('index');
        Route::get('/create', [StockController::class, 'ordersCreate'])->name('create');
        Route::post('/', [StockController::class, 'ordersStore'])->name('store');
        Route::get('/{order}', [StockController::class, 'ordersShow'])->whereNumber('order')->name('show');
        Route::get('/{order}/edit', [StockController::class, 'ordersEdit'])->whereNumber('order')->name('edit');
        Route::put('/{order}', [StockController::class, 'ordersUpdate'])->whereNumber('order')->name('update');
        Route::delete('/{order}', [StockController::class, 'ordersDestroy'])->whereNumber('order')->name('destroy');

        Route::get('/data/table', [StockController::class, 'ordersData'])->name('data');
        Route::get('/data/search', [StockController::class, 'ordersSearch'])->name('search');
        Route::get('/data/{order}', [StockController::class, 'ordersDetail'])->whereNumber('order')->name('detail');
        Route::post('/{order}/receive', [StockController::class, 'ordersReceive'])->whereNumber('order')->name('receive');
        Route::get('/export/excel', [StockController::class, 'ordersExportExcel'])->name('export.excel');
    });

    Route::get('/data/stats', [StockController::class, 'stats'])->name('stats');
});
