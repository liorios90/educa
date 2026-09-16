<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;

Route::middleware(['auth', 'verified', 'role:Admin'])
    ->prefix('Admin')
    ->name('Admin.')
    ->group(function () {
        //Route::view('/', 'Admin.home')->name('home');
        Route::get('prueba', [AdminController::class, 'lio'])->name('lio');


    });
