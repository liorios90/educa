<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\EstablecimientoEstructuraController;
use App\Http\Controllers\ImportRepresentantesController;
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
        Route::get('padres/importar', [ImportRepresentantesController::class, 'create'])->name('padres.import');
        Route::post('padres/importar', [ImportRepresentantesController::class, 'store'])->name('padres.import.store');
        Route::get('padres/importar/{importData}', [ImportRepresentantesController::class, 'show'])->name('padres.import.show');
        Route::get('padres/{padre}/editar', [PadreController::class, 'edit'])->name('padres.edit');
        Route::patch('padres/{padre}', [PadreController::class, 'update'])->name('padres.update');
        Route::delete('padres/{padre}', [PadreController::class, 'destroy'])->name('padres.destroy');
        Route::get('alumnos', [AlumnoController::class, 'index'])->name('alumnos');
        Route::get('alumnos/crear', [AlumnoController::class, 'create'])->name('alumnos.create');
        Route::post('alumnos', [AlumnoController::class, 'store'])->name('alumnos.store');
        Route::get('alumnos/{alumno}/editar', [AlumnoController::class, 'edit'])->name('alumnos.edit');
        Route::patch('alumnos/{alumno}', [AlumnoController::class, 'update'])->name('alumnos.update');
        Route::delete('alumnos/{alumno}', [AlumnoController::class, 'destroy'])->name('alumnos.destroy');
        Route::get('estructura', [EstablecimientoEstructuraController::class, 'edit'])->name('estructura');
        Route::put('estructura', [EstablecimientoEstructuraController::class, 'update'])->name('estructura.update');
        Route::get('empleados', [EmpleadoController::class, 'index'])->name('empleados');
        Route::get('empleados/crear', [EmpleadoController::class, 'create'])->name('empleados.create');
        Route::post('empleados', [EmpleadoController::class, 'store'])->name('empleados.store');
        Route::get('empleados/{empleado}/editar', [EmpleadoController::class, 'edit'])->name('empleados.edit');
        Route::patch('empleados/{empleado}', [EmpleadoController::class, 'update'])->name('empleados.update');
        Route::delete('empleados/{empleado}', [EmpleadoController::class, 'destroy'])->name('empleados.destroy');

    });
