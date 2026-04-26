<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\Chatbot\Http\Controllers\ChatbotController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:chatbot'])
    ->prefix('extensions/chatbot')
    ->name('chatbot.')
    ->group(function () {
        Route::get('/', [ChatbotController::class, 'index'])->name('index');

        Route::get('/data/rooms', [ChatbotController::class, 'roomsData'])->name('rooms.data');
        Route::get('/data/users', [ChatbotController::class, 'usersData'])->name('users.data');
        Route::post('/rooms', [ChatbotController::class, 'storeRoom'])->name('rooms.store');
        Route::put('/rooms/{room}', [ChatbotController::class, 'updateRoom'])->name('rooms.update');
        Route::delete('/rooms/{room}', [ChatbotController::class, 'destroyRoom'])->name('rooms.destroy');

        Route::get('/data/messages', [ChatbotController::class, 'messagesData'])->name('messages.data');
        Route::get('/data/search', [ChatbotController::class, 'searchData'])->name('search.data');
        Route::post('/messages/send', [ChatbotController::class, 'sendMessage'])->name('messages.send');
        Route::delete('/messages/{message}', [ChatbotController::class, 'destroyMessage'])->name('messages.destroy');

        Route::get('/data/stats', [ChatbotController::class, 'stats'])->name('stats');
    });
