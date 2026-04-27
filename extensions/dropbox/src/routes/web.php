<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\Dropbox\Http\Controllers\DropboxController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:dropbox'])
    ->prefix('extensions/dropbox')
    ->name('dropbox.')
    ->group(function () {
        Route::get('/', [DropboxController::class, 'index'])->name('index');

        Route::get('/oauth/connect', [DropboxController::class, 'connect'])->name('oauth.connect');
        Route::get('/oauth/callback', [DropboxController::class, 'callback'])->name('oauth.callback');
        Route::post('/oauth/disconnect', [DropboxController::class, 'disconnect'])->name('oauth.disconnect');

        Route::get('/data/files', [DropboxController::class, 'filesData'])->name('files.data');
        Route::get('/data/stats', [DropboxController::class, 'stats'])->name('stats');
        Route::get('/data/trash', [DropboxController::class, 'trashData'])->name('trash.data');
        Route::get('/data/search', [DropboxController::class, 'search'])->name('search');

        Route::post('/folders', [DropboxController::class, 'createFolder'])->name('folders.store');
        Route::post('/files/upload', [DropboxController::class, 'upload'])->name('files.upload');
        Route::patch('/files/{fileId}/rename', [DropboxController::class, 'rename'])->where(['fileId' => '.+'])->name('files.rename');
        Route::patch('/files/{fileId}/move', [DropboxController::class, 'move'])->where(['fileId' => '.+'])->name('files.move');
        Route::post('/files/{fileId}/copy', [DropboxController::class, 'copy'])->where(['fileId' => '.+'])->name('files.copy');
        Route::post('/files/{fileId}/share', [DropboxController::class, 'share'])->where(['fileId' => '.+'])->name('files.share');
        Route::get('/files/{fileId}/open', [DropboxController::class, 'open'])->where(['fileId' => '.+'])->name('files.open');
        Route::delete('/files/{fileId}', [DropboxController::class, 'delete'])->where(['fileId' => '.+'])->name('files.delete');
        Route::post('/files/{fileId}/restore', [DropboxController::class, 'restore'])->where(['fileId' => '.+'])->name('files.restore');
        Route::get('/files/{fileId}/download', [DropboxController::class, 'download'])->where(['fileId' => '.+'])->name('files.download');
        Route::delete('/trash', [DropboxController::class, 'emptyTrash'])->name('trash.empty');
    });
