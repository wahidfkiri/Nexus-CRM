<?php

use Illuminate\Support\Facades\Route;
use Modules\TrelloIntegration\Http\Controllers\TrelloController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:trello-integration'])
    ->prefix('extensions/trello-integration')
    ->name('trello-integration.')
    ->group(function () {
        Route::get('/', [TrelloController::class, 'index'])->name('index');
        Route::get('/oauth/connect', [TrelloController::class, 'connect'])->name('connect');
        Route::get('/oauth/callback', [TrelloController::class, 'callback'])->name('callback');
        Route::post('/oauth/finalize', [TrelloController::class, 'finalizeOauth'])->name('oauth.finalize');
        Route::post('/disconnect', [TrelloController::class, 'disconnect'])->name('disconnect');
        Route::post('/sync', [TrelloController::class, 'sync'])->name('sync');

        Route::get('/boards/{board}', [TrelloController::class, 'board'])->whereNumber('board')->name('boards.show');
        Route::get('/cards/{card}', [TrelloController::class, 'showCard'])->whereNumber('card')->name('cards.show');
        Route::put('/cards/{card}', [TrelloController::class, 'updateCard'])->whereNumber('card')->name('cards.update');
        Route::put('/cards/{card}/move', [TrelloController::class, 'moveCard'])->whereNumber('card')->name('cards.move');
        Route::delete('/cards/{card}', [TrelloController::class, 'archiveCard'])->whereNumber('card')->name('cards.archive');
        Route::post('/lists/{list}/cards', [TrelloController::class, 'storeCard'])->whereNumber('list')->name('lists.cards.store');
    });
