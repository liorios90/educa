<?php

use App\Http\Controllers\SecretariaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:Secretaria'])
    ->prefix('secretaria')
    ->name('secretaria.')
    ->group(function () {
        Route::view('/', 'secretaria.home')->name('home');
        Route::get('/prueba', [SecretariaController::class, 'prueba'])->name('prueba');
        Route::get('/asistente', [SecretariaController::class, 'asistente'])->name('asistente');
        Route::post('/asistente', [SecretariaController::class, 'store'])->name('asistente.store');
    });
