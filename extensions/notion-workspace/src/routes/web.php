<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\NotionWorkspace\Http\Controllers\NotionWorkspaceController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:notion-workspace'])
    ->prefix('extensions/notion-workspace')
    ->name('notion-workspace.')
    ->group(function () {
        Route::get('/', [NotionWorkspaceController::class, 'index'])->name('index');

        Route::get('/data/tree', [NotionWorkspaceController::class, 'treeData'])->name('tree.data');
        Route::get('/pages/{page}', [NotionWorkspaceController::class, 'show'])->whereNumber('page')->name('pages.show');
        Route::post('/pages', [NotionWorkspaceController::class, 'store'])->name('pages.store');
        Route::put('/pages/{page}', [NotionWorkspaceController::class, 'update'])->whereNumber('page')->name('pages.update');
        Route::delete('/pages/{page}', [NotionWorkspaceController::class, 'destroy'])->whereNumber('page')->name('pages.destroy');
        Route::post('/pages/{page}/duplicate', [NotionWorkspaceController::class, 'duplicate'])->whereNumber('page')->name('pages.duplicate');
        Route::patch('/pages/{page}/favorite', [NotionWorkspaceController::class, 'toggleFavorite'])->whereNumber('page')->name('pages.favorite');
        Route::patch('/pages/{page}/move', [NotionWorkspaceController::class, 'move'])->whereNumber('page')->name('pages.move');
        Route::put('/pages/{page}/shares', [NotionWorkspaceController::class, 'syncShares'])->whereNumber('page')->name('pages.shares.sync');
        Route::get('/pages/{page}/activities', [NotionWorkspaceController::class, 'activities'])->whereNumber('page')->name('pages.activities');
    });

