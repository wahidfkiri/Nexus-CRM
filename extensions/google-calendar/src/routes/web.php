<?php

use Illuminate\Support\Facades\Route;
use Vendor\GoogleCalendar\Http\Controllers\GoogleCalendarController;

Route::middleware(['web', 'auth', 'tenant'])
    ->prefix('extensions/google-calendar')
    ->name('google-calendar.')
    ->group(function () {
        Route::get('/', [GoogleCalendarController::class, 'index'])->name('index');

        Route::get('/oauth/connect', [GoogleCalendarController::class, 'connect'])->name('oauth.connect');
        Route::get('/oauth/callback', [GoogleCalendarController::class, 'callback'])->name('oauth.callback');
        Route::post('/oauth/disconnect', [GoogleCalendarController::class, 'disconnect'])->name('oauth.disconnect');

        Route::get('/data/calendars', [GoogleCalendarController::class, 'calendarsData'])->name('calendars.data');
        Route::post('/calendar/select', [GoogleCalendarController::class, 'selectCalendar'])->name('calendar.select');

        Route::get('/data/events', [GoogleCalendarController::class, 'eventsData'])->name('events.data');
        Route::get('/data/stats', [GoogleCalendarController::class, 'stats'])->name('stats');
        Route::post('/sync', [GoogleCalendarController::class, 'sync'])->name('sync');

        Route::post('/events', [GoogleCalendarController::class, 'storeEvent'])->name('events.store');
        Route::put('/events/{calendarId}/{eventId}', [GoogleCalendarController::class, 'updateEvent'])
            ->where(['calendarId' => '.+', 'eventId' => '.+'])
            ->name('events.update');
        Route::delete('/events/{calendarId}/{eventId}', [GoogleCalendarController::class, 'destroyEvent'])
            ->where(['calendarId' => '.+', 'eventId' => '.+'])
            ->name('events.destroy');
    });
