<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Ce fichier ne déclare que les URL, leurs contrôleurs et les contrôles d’accès.
Route::middleware('active')->group(function (): void {
    Route::get('/', [UserController::class, 'index'])->name('index');

    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [UserController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [UserController::class, 'login'])->middleware('throttle:6,1')->name('login.store');
        Route::get('/register', [UserController::class, 'showRegistrationForm'])->name('register');
        Route::post('/register', [UserController::class, 'register'])->name('register.store');
    });

    Route::middleware('auth')->group(function (): void {
        Route::middleware('organizer')->group(function (): void {
            Route::get('/tournament', [TournamentController::class, 'create'])->name('tournaments.create');
            Route::post('/tournament', [TournamentController::class, 'store'])->name('tournaments.store');
        });

        Route::post('/logout', [UserController::class, 'logout'])->name('logout');
        Route::get('/home', [HomeController::class, 'index'])->name('home');
        Route::get('/sport/{name?}', [TournamentController::class, 'index'])->name('sport');
        Route::get('/tournoi/{id}/inscription', [TournamentController::class, 'showRegistration'])
            ->whereNumber('id')->name('tournament.registration');
        Route::post('/tournoi/{id}/inscription', [TournamentController::class, 'register'])
            ->whereNumber('id')->name('tournament.registration.store');
        Route::get('/profil', [ProfilController::class, 'index'])->name('profil');
    });
});
