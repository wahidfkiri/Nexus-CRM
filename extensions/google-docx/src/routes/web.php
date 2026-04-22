<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\GoogleDocx\Http\Controllers\GoogleDocxController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:google-docx'])
    ->prefix('extensions/google-docx')
    ->name('google-docx.')
    ->group(function () {
        Route::get('/', [GoogleDocxController::class, 'index'])->name('index');

        Route::get('/oauth/connect', [GoogleDocxController::class, 'connect'])->name('oauth.connect');
        Route::get('/oauth/callback', [GoogleDocxController::class, 'callback'])->name('oauth.callback');
        Route::post('/oauth/disconnect', [GoogleDocxController::class, 'disconnect'])->name('oauth.disconnect');

        Route::get('/data/stats', [GoogleDocxController::class, 'stats'])->name('stats');
        Route::get('/data/documents', [GoogleDocxController::class, 'documentsData'])->name('documents.data');

        Route::post('/documents', [GoogleDocxController::class, 'createDocument'])->name('documents.store');
        Route::get('/documents/{documentId}', [GoogleDocxController::class, 'showDocument'])->where(['documentId' => '[^/]+'])->name('documents.show');
        Route::patch('/documents/{documentId}/rename', [GoogleDocxController::class, 'renameDocument'])->where(['documentId' => '[^/]+'])->name('documents.rename');
        Route::post('/documents/{documentId}/duplicate', [GoogleDocxController::class, 'duplicateDocument'])->where(['documentId' => '[^/]+'])->name('documents.duplicate');
        Route::delete('/documents/{documentId}', [GoogleDocxController::class, 'deleteDocument'])->where(['documentId' => '[^/]+'])->name('documents.delete');

        Route::post('/documents/{documentId}/append', [GoogleDocxController::class, 'appendText'])->where(['documentId' => '[^/]+'])->name('documents.append');
        Route::post('/documents/{documentId}/replace', [GoogleDocxController::class, 'replaceText'])->where(['documentId' => '[^/]+'])->name('documents.replace');
        Route::get('/documents/{documentId}/export', [GoogleDocxController::class, 'exportDocument'])->where(['documentId' => '[^/]+'])->name('documents.export');
    });
