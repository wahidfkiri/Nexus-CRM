<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\DraftController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GlobalSettingsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SecurityValidationDemoController;
use App\Http\Controllers\SuperAdmin\TenantAdminController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
   Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
  
});