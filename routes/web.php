<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::middleware('role:Admin')->group(function () {
        Route::get('/admin/usuarios', [UserController::class, 'index'])->name('admin.users');
        Route::get('/admin/usuarios/crear', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/admin/usuarios', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/admin/usuarios/{user}/editar', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::patch('/admin/usuarios/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/admin/usuarios/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/sistemas.php';
require __DIR__.'/auth.php';
