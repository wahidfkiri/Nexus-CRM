<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\GoogleGmail\Http\Controllers\GoogleGmailController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:google-gmail'])
    ->prefix('extensions/google-gmail')
    ->name('google-gmail.')
    ->group(function () {
        Route::get('/', [GoogleGmailController::class, 'index'])->name('index');

        Route::get('/oauth/connect', [GoogleGmailController::class, 'connect'])->name('oauth.connect');
        Route::get('/oauth/callback', [GoogleGmailController::class, 'callback'])->name('oauth.callback');
        Route::post('/oauth/disconnect', [GoogleGmailController::class, 'disconnect'])->name('oauth.disconnect');

        Route::get('/data/stats', [GoogleGmailController::class, 'stats'])->name('stats');
        Route::get('/data/labels', [GoogleGmailController::class, 'labelsData'])->name('labels.data');
        Route::get('/data/messages', [GoogleGmailController::class, 'messagesData'])->name('messages.data');
        Route::get('/data/settings', [GoogleGmailController::class, 'settingsData'])->name('settings.data');
        Route::post('/data/settings', [GoogleGmailController::class, 'saveSettings'])->name('settings.save');

        Route::get('/threads/{threadId}', [GoogleGmailController::class, 'showThread'])
            ->where(['threadId' => '[^/]+'])
            ->name('threads.show');

        Route::get('/messages/{messageId}', [GoogleGmailController::class, 'showMessage'])
            ->where(['messageId' => '[^/]+'])
            ->name('messages.show');

        Route::post('/messages/send', [GoogleGmailController::class, 'sendEmail'])->name('messages.send');
        Route::post('/messages/{messageId}/reply', [GoogleGmailController::class, 'replyEmail'])
            ->where(['messageId' => '[^/]+'])
            ->name('messages.reply');
        Route::post('/messages/{messageId}/forward', [GoogleGmailController::class, 'forwardEmail'])
            ->where(['messageId' => '[^/]+'])
            ->name('messages.forward');

        Route::post('/messages/{messageId}/mark-read', [GoogleGmailController::class, 'markRead'])
            ->where(['messageId' => '[^/]+'])
            ->name('messages.mark-read');
        Route::post('/messages/{messageId}/mark-unread', [GoogleGmailController::class, 'markUnread'])
            ->where(['messageId' => '[^/]+'])
            ->name('messages.mark-unread');
        Route::post('/messages/{messageId}/star', [GoogleGmailController::class, 'star'])
            ->where(['messageId' => '[^/]+'])
            ->name('messages.star');
        Route::post('/messages/{messageId}/unstar', [GoogleGmailController::class, 'unstar'])
            ->where(['messageId' => '[^/]+'])
            ->name('messages.unstar');
        Route::post('/messages/{messageId}/archive', [GoogleGmailController::class, 'archive'])
            ->where(['messageId' => '[^/]+'])
            ->name('messages.archive');
        Route::post('/messages/{messageId}/trash', [GoogleGmailController::class, 'trash'])
            ->where(['messageId' => '[^/]+'])
            ->name('messages.trash');
        Route::post('/messages/{messageId}/untrash', [GoogleGmailController::class, 'untrash'])
            ->where(['messageId' => '[^/]+'])
            ->name('messages.untrash');

        Route::delete('/messages/{messageId}', [GoogleGmailController::class, 'deleteMessage'])
            ->where(['messageId' => '[^/]+'])
            ->name('messages.delete');

        Route::get('/messages/{messageId}/attachments/{attachmentId}/download', [GoogleGmailController::class, 'downloadAttachment'])
            ->where(['messageId' => '[^/]+', 'attachmentId' => '[^/]+'])
            ->name('messages.attachments.download');
    });
