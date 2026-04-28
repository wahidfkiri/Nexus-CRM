<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\NotionWorkspace\Http\Controllers\NotionApiController;
use NexusExtensions\NotionWorkspace\Http\Controllers\NotionLinkController;
use NexusExtensions\NotionWorkspace\Http\Controllers\NotionWorkspaceController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:notion-workspace'])
    ->prefix('extensions/notion-workspace')
    ->name('notion-workspace.')
    ->group(function () {
        Route::get('/', [NotionWorkspaceController::class, 'index'])->name('index');
        Route::get('/oauth/connect', [NotionWorkspaceController::class, 'connect'])->name('connect');
        Route::get('/oauth/callback', [NotionWorkspaceController::class, 'callback'])->name('callback');
        Route::post('/disconnect', [NotionWorkspaceController::class, 'disconnect'])->name('disconnect');

        Route::get('/pages/search', [NotionApiController::class, 'pages'])->name('pages.search');
        Route::post('/pages', [NotionApiController::class, 'store'])->name('pages.store');
        Route::get('/pages/{pageId}', [NotionApiController::class, 'show'])
            ->where('pageId', '[A-Za-z0-9\-]+')
            ->name('pages.show');

        Route::get('/links', [NotionLinkController::class, 'index'])->name('links.index');
        Route::post('/links', [NotionLinkController::class, 'store'])->name('links.store');
        Route::put('/links/{link}', [NotionLinkController::class, 'update'])->whereNumber('link')->name('links.update');
        Route::delete('/links/{link}', [NotionLinkController::class, 'destroy'])->whereNumber('link')->name('links.destroy');
    });