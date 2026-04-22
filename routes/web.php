<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GlobalSettingsController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SecurityValidationDemoController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/', function () {
        return auth()->check() ? redirect('/dashboard') : redirect('/login');
    });

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
        Route::post('/register', [AuthController::class, 'register']);

        Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
        Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

        Route::get('/password/reset', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    });

    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify')
        ->middleware('signed');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->name('verification.send');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

    Route::middleware(['auth', 'tenant'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/home', [DashboardController::class, 'index'])->name('home');

        Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
        Route::post('/onboarding/profile', [OnboardingController::class, 'saveProfile'])->name('onboarding.profile');
        Route::post('/onboarding/company', [OnboardingController::class, 'saveCompany'])->name('onboarding.company');
        Route::post('/onboarding/sector', [OnboardingController::class, 'saveSector'])->name('onboarding.sector');
        Route::post('/onboarding/apps', [OnboardingController::class, 'saveApps'])->name('onboarding.apps');
        Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');

        Route::get('/applications', fn () => redirect()->route('marketplace.index'))->name('applications');
        Route::get('/settings/global', [GlobalSettingsController::class, 'show'])->name('settings.global');
        Route::put('/settings/global', [GlobalSettingsController::class, 'update'])->name('settings.global.update');
        Route::get('/profile-settings', [ProfileController::class, 'show'])->name('profile-settings');
        Route::put('/profile-settings', [ProfileController::class, 'update'])->name('profile-settings.update');
        Route::get('/security/validation-demo', [SecurityValidationDemoController::class, 'create'])->name('security.validation-demo');
        Route::post('/security/validation-demo', [SecurityValidationDemoController::class, 'store'])->name('security.validation-demo.store');
        Route::get('/analytics', fn () => view('analytics'))->name('analytics');
        Route::get('/tables', fn () => view('tables'))->name('tables');
    });
});
