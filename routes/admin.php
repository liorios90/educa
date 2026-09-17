<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\PadreController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:Admin'])
    ->prefix('Admin')
    ->name('Admin.')
    ->group(function () {
        // Route::view('/', 'Admin.home')->name('home');
        Route::get('prueba', [AdminController::class, 'lio'])->name('lio');
        Route::get('padres', [PadreController::class, 'index'])->name('padres');
        Route::get('padres/crear', [PadreController::class, 'create'])->name('padres.create');
        Route::post('padres', [PadreController::class, 'store'])->name('padres.store');
        Route::get('padres/{padre}/editar', [PadreController::class, 'edit'])->name('padres.edit');
        Route::patch('padres/{padre}', [PadreController::class, 'update'])->name('padres.update');
        Route::delete('padres/{padre}', [PadreController::class, 'destroy'])->name('padres.destroy');
        Route::get('alumnos', [AlumnoController::class, 'index'])->name('alumnos');
        Route::get('alumnos/crear', [AlumnoController::class, 'create'])->name('alumnos.create');
        Route::post('alumnos', [AlumnoController::class, 'store'])->name('alumnos.store');
        Route::get('alumnos/{alumno}/editar', [AlumnoController::class, 'edit'])->name('alumnos.edit');
        Route::patch('alumnos/{alumno}', [AlumnoController::class, 'update'])->name('alumnos.update');
        Route::delete('alumnos/{alumno}', [AlumnoController::class, 'destroy'])->name('alumnos.destroy');

    });
