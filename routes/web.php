<?php

use App\Http\Controllers\Auth\BridgeConsumeController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

// Local credential login (staff fallback + development).
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Joomla bridge SSO consume endpoint.
Route::get('auth/bridge/consume', BridgeConsumeController::class)
    ->middleware('throttle:10,1')
    ->name('bridge.consume');

// Authenticated areas — every privileged area is behind role middleware AND
// the `active` middleware so disabled accounts are dropped immediately.
Route::middleware(['auth', 'active'])->group(function () {
    Route::livewire('dashboard', 'student.dashboard')
        ->name('student.dashboard');
});

Route::middleware(['auth', 'active', 'role:trainer,super-admin,academy-admin'])
    ->prefix('trainer')->name('trainer.')->group(function () {
        Route::livewire('dashboard', 'trainer.dashboard')->name('dashboard');
    });

Route::middleware(['auth', 'active', 'role:super-admin,academy-admin'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('dashboard', 'admin.dashboard')->name('dashboard');
    });
