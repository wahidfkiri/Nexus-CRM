<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\GoogleSheets\Http\Controllers\GoogleSheetsController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:google-sheets'])
    ->prefix('extensions/google-sheets')
    ->name('google-sheets.')
    ->group(function () {

        // Page principale
        Route::get('/', [GoogleSheetsController::class, 'index'])->name('index');

        // OAuth
        Route::get('/oauth/connect',    [GoogleSheetsController::class, 'connect'])->name('oauth.connect');
        Route::get('/oauth/callback',   [GoogleSheetsController::class, 'callback'])->name('oauth.callback');
        Route::post('/oauth/disconnect',[GoogleSheetsController::class, 'disconnect'])->name('oauth.disconnect');

        // Stats
        Route::get('/data/stats', [GoogleSheetsController::class, 'stats'])->name('stats');

        // Spreadsheets
        Route::get('/data/spreadsheets',           [GoogleSheetsController::class, 'spreadsheetsData'])->name('spreadsheets.data');
        Route::post('/spreadsheets',               [GoogleSheetsController::class, 'createSpreadsheet'])->name('spreadsheets.store');
        Route::get('/spreadsheets/{spreadsheetId}',[GoogleSheetsController::class, 'showSpreadsheet'])->where(['spreadsheetId' => '.+'])->name('spreadsheets.show');
        Route::patch('/spreadsheets/{spreadsheetId}/rename',   [GoogleSheetsController::class, 'renameSpreadsheet'])->where(['spreadsheetId' => '.+'])->name('spreadsheets.rename');
        Route::post('/spreadsheets/{spreadsheetId}/duplicate', [GoogleSheetsController::class, 'duplicateSpreadsheet'])->where(['spreadsheetId' => '.+'])->name('spreadsheets.duplicate');
        Route::delete('/spreadsheets/{spreadsheetId}',         [GoogleSheetsController::class, 'deleteSpreadsheet'])->where(['spreadsheetId' => '.+'])->name('spreadsheets.delete');

        // Sheets (onglets)
        Route::post('/spreadsheets/{spreadsheetId}/sheets',                   [GoogleSheetsController::class, 'addSheet'])->where(['spreadsheetId' => '.+'])->name('sheets.store');
        Route::patch('/spreadsheets/{spreadsheetId}/sheets/{sheetId}/rename', [GoogleSheetsController::class, 'renameSheet'])->where(['spreadsheetId' => '.+'])->name('sheets.rename');
        Route::delete('/spreadsheets/{spreadsheetId}/sheets/{sheetId}',       [GoogleSheetsController::class, 'deleteSheet'])->where(['spreadsheetId' => '.+'])->name('sheets.delete');

        // Data / Cellules
        Route::get('/spreadsheets/{spreadsheetId}/values',         [GoogleSheetsController::class, 'readRange'])->where(['spreadsheetId' => '.+'])->name('values.read');
        Route::put('/spreadsheets/{spreadsheetId}/values',         [GoogleSheetsController::class, 'writeRange'])->where(['spreadsheetId' => '.+'])->name('values.write');
        Route::post('/spreadsheets/{spreadsheetId}/values/append', [GoogleSheetsController::class, 'appendRows'])->where(['spreadsheetId' => '.+'])->name('values.append');
        Route::delete('/spreadsheets/{spreadsheetId}/values',      [GoogleSheetsController::class, 'clearRange'])->where(['spreadsheetId' => '.+'])->name('values.clear');
        Route::post('/spreadsheets/{spreadsheetId}/values/batch-read',  [GoogleSheetsController::class, 'batchRead'])->where(['spreadsheetId' => '.+'])->name('values.batch-read');
        Route::post('/spreadsheets/{spreadsheetId}/values/batch-write', [GoogleSheetsController::class, 'batchWrite'])->where(['spreadsheetId' => '.+'])->name('values.batch-write');
    });
