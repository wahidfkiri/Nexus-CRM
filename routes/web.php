<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Routes d'authentification web
Route::middleware(['web'])->group(function () {
    
    // Page d'accueil
    Route::get('/', function () {
        if (auth()->check()) {
            return redirect('/dashboard');
        }
        return redirect('/login');
    });
    
    // Routes d'authentification
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/password/reset', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    
    // Routes protégées
    Route::middleware(['auth','web','tenant'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/home', [DashboardController::class, 'index'])->name('home');
        
               
        Route::get('/create-invoice', function () {
            return view('create-invoice');
        })->name('create-invoice');
        
        Route::get('/applications', function () {
            return view('applications');
        })->name('applications');
        
        Route::get('/profile-settings', function () {
            return view('profile-settings');
        })->name('profile-settings');
        
        Route::get('/analytics', function () {
            return view('analytics');
        })->name('analytics');
        
        Route::get('/tables', function () {
            return view('tables');
        })->name('tables');
    });
});

// Routes API (Sanctum) - gardez-les séparées
Route::prefix('api')->group(function () {
    Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [App\Http\Controllers\Api\AuthController::class, 'user'])->middleware('auth:sanctum');
});