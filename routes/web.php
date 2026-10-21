<?php

use App\Http\Controllers\CalificarInsumosController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiagnosticoEstudianteController;
use App\Http\Controllers\EnrollmentSubmissionController;
use App\Http\Controllers\HorarioDocenteController;
use App\Http\Controllers\NavigationHubController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportRunController;
use App\Http\Controllers\SecretariaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/privacidad', 'privacy')->name('privacy');
Route::view('/terminos', 'terms')->name('terms');
Route::view('/cookies', 'cookies')->name('cookies');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:Docente')
        ->prefix('docente')
        ->name('Docente.')
        ->group(function () {
            Route::get('/horarios', HorarioDocenteController::class)->name('horarios');
            Route::get('/calificar', [CalificarInsumosController::class, 'index'])->name('calificar.index');
            Route::post('/calificar', [CalificarInsumosController::class, 'store'])->name('calificar.store');
            Route::get('/calificar/editar', [CalificarInsumosController::class, 'editar'])->name('calificar.editar');
            Route::post('/calificar/actividades', [CalificarInsumosController::class, 'storeActividad'])->name('calificar.actividades.store');
            Route::put('/calificar/actividades/{actividad}', [CalificarInsumosController::class, 'updateActividad'])->name('calificar.actividades.update');
            Route::get('/asistente', [SecretariaController::class, 'asistente'])->name('asistente');
            Route::post('/asistente', [SecretariaController::class, 'store'])->name('asistente.store');
            Route::get('/diagnostico', [DiagnosticoEstudianteController::class, 'index'])->name('diagnostico.index');
            Route::post('/diagnostico', [DiagnosticoEstudianteController::class, 'seleccionar'])->name('diagnostico.seleccionar');
            Route::post('/diagnostico/estudiantes', [DiagnosticoEstudianteController::class, 'store'])->name('diagnostico.store');
            Route::post('/diagnostico/grupo', [DiagnosticoEstudianteController::class, 'grupo'])->name('diagnostico.grupo');
        });

    Route::get('/menu/{navigationItem}', [NavigationHubController::class, 'show'])->name('navigation.hub');
    Route::get('/reportes', [ReportRunController::class, 'index'])->name('reports.index');
    Route::get('/reportes/{reportDefinition}/pdf', [ReportRunController::class, 'pdf'])
        ->middleware('throttle:20,1')
        ->name('reports.pdf');
    Route::get('/reportes/{reportDefinition}', [ReportRunController::class, 'show'])->name('reports.show');

    Route::middleware('role:Admin')->group(function () {
        // Route::get('/admin/usuarios', [UserController::class, 'index'])->name('admin.users');
        // Route::get('/admin/usuarios/crear', [UserController::class, 'create'])->name('admin.users.create');
        // Route::post('/admin/usuarios', [UserController::class, 'store'])->name('admin.users.store');
        // Route::get('/admin/usuarios/{user}/editar', [UserController::class, 'edit'])->name('admin.users.edit');
        // Route::patch('/admin/usuarios/{user}', [UserController::class, 'update'])->name('admin.users.update');
        // Route::delete('/admin/usuarios/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'role:Padre'])
    ->prefix('matriculas')
    ->name('matriculas.')
    ->group(function () {
        Route::get('/', [EnrollmentSubmissionController::class, 'index'])->name('index');
        Route::get('/crear', [EnrollmentSubmissionController::class, 'create'])->name('create');
        Route::get('/{enrollmentSubmission}/editar', [EnrollmentSubmissionController::class, 'edit'])->name('edit');
        Route::put('/{enrollmentSubmission}', [EnrollmentSubmissionController::class, 'update'])->name('update');
        Route::post('/', [EnrollmentSubmissionController::class, 'store'])->name('store');
    });

require __DIR__.'/admin.php';
require __DIR__.'/secretaria.php';
require __DIR__.'/sistemas.php';
require __DIR__.'/auth.php';
