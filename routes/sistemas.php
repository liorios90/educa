<?php

use App\Http\Controllers\NavigationItemController;
use App\Http\Controllers\SistemasController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:Sistemas'])
    ->prefix('sistemas')
    ->name('sistemas.')
    ->group(function () {
        Route::view('/', 'sistemas.home')->name('home');
        Route::get('/prueba', [SistemasController::class, 'prueba'])->name('prueba');

        Route::get('/menu', [NavigationItemController::class, 'index'])->name('navigation-items.index');
        Route::get('/menu/crear', [NavigationItemController::class, 'create'])->name('navigation-items.create');
        Route::post('/menu', [NavigationItemController::class, 'store'])->name('navigation-items.store');
        Route::get('/menu/{navigationItem}/editar', [NavigationItemController::class, 'edit'])->name('navigation-items.edit');
        Route::patch('/menu/{navigationItem}', [NavigationItemController::class, 'update'])->name('navigation-items.update');
        Route::delete('/menu/{navigationItem}', [NavigationItemController::class, 'destroy'])->name('navigation-items.destroy');
    });
