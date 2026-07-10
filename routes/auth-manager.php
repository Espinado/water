<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function (): void {
    Volt::route('login', 'pages.auth.login')
        ->name('login.manager');

    Volt::route('forgot-password', 'pages.auth.forgot-password')
        ->name('manager.password.request');

    Volt::route('reset-password/{token}', 'pages.auth.reset-password')
        ->name('manager.password.reset');
});

Route::middleware('auth')->group(function (): void {
    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('manager.verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('manager.verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('manager.password.confirm');
});
