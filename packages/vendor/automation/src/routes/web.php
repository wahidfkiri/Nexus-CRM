<?php

use Illuminate\Support\Facades\Route;
use Vendor\Automation\Http\Controllers\AutomationSuggestionController;

Route::middleware(['web', 'auth', 'tenant'])
    ->prefix('automation')
    ->name('automation.')
    ->group(function () {
        Route::get('/suggestions', [AutomationSuggestionController::class, 'index'])->name('suggestions.index');
        Route::post('/suggestions/accept', [AutomationSuggestionController::class, 'bulkAccept'])->name('suggestions.accept.bulk');
        Route::post('/suggestions/reject', [AutomationSuggestionController::class, 'bulkReject'])->name('suggestions.reject.bulk');
        Route::post('/suggestions/{suggestion}/accept', [AutomationSuggestionController::class, 'accept'])->name('suggestions.accept');
        Route::post('/suggestions/{suggestion}/reject', [AutomationSuggestionController::class, 'reject'])->name('suggestions.reject');
    });
