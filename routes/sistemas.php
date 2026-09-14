<?php

use App\Crud\CrudRegistry;
use App\Http\Controllers\GenericCrudController;
use App\Http\Controllers\NavigationItemController;
use App\Http\Controllers\SistemasController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:Sistemas'])
    ->prefix('sistemas')
    ->name('sistemas.')
    ->group(function () {
        Route::view('/', 'sistemas.home')->name('home');
        Route::get('/prueba', [SistemasController::class, 'prueba'])->name('prueba');
        Route::get('/prueba', [SistemasController::class, 'prueba2'])->name('prueba2');
        Route::get('/menu', [NavigationItemController::class, 'index'])->name('navigation-items.index');
        Route::get('/menu/crear', [NavigationItemController::class, 'create'])->name('navigation-items.create');
        Route::post('/menu', [NavigationItemController::class, 'store'])->name('navigation-items.store');
        Route::get('/menu/{navigationItem}/editar', [NavigationItemController::class, 'edit'])->name('navigation-items.edit');
        Route::patch('/menu/{navigationItem}', [NavigationItemController::class, 'update'])->name('navigation-items.update');
        Route::delete('/menu/{navigationItem}', [NavigationItemController::class, 'destroy'])->name('navigation-items.destroy');

        foreach (app(CrudRegistry::class)->slugs() as $slug) {
            Route::prefix("catalogos/{$slug}")
                ->name("crud.{$slug}.")
                ->group(function () {
                    Route::get('/', [GenericCrudController::class, 'index'])->name('index');
                    Route::get('/crear', [GenericCrudController::class, 'create'])->name('create');
                    Route::post('/', [GenericCrudController::class, 'store'])->name('store');
                    Route::get('/{record}/editar', [GenericCrudController::class, 'edit'])->name('edit')->whereNumber('record');
                    Route::patch('/{record}', [GenericCrudController::class, 'update'])->name('update')->whereNumber('record');
                    Route::delete('/{record}', [GenericCrudController::class, 'destroy'])->name('destroy')->whereNumber('record');
                });
        }
    });
