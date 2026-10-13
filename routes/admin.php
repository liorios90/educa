<?php

use App\Http\Controllers\Admin\ConfiguracionAsignaturasController;
use App\Http\Controllers\Admin\EstablecimientoPeriodoConfiguracionGradoController;
use App\Http\Controllers\Admin\HorarioClaseController;
use App\Http\Controllers\Admin\ProcesoCierresController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\EstablecimientoAsignaturaController;
use App\Http\Controllers\EstablecimientoAulaController;
use App\Http\Controllers\EstablecimientoEstructuraController;
use App\Http\Controllers\EstablecimientoPeriodoEvaluacionController;
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
        Route::get('estructura', [EstablecimientoEstructuraController::class, 'index'])->name('estructura');
        Route::get('estructura/{oferta}', [EstablecimientoEstructuraController::class, 'edit'])->name('estructura.edit');
        Route::put('estructura/{oferta}', [EstablecimientoEstructuraController::class, 'update'])->name('estructura.update');
        Route::get('periodo', [EstablecimientoPeriodoEvaluacionController::class, 'edit'])->name('periodo');
        Route::put('periodo', [EstablecimientoPeriodoEvaluacionController::class, 'update'])->name('periodo.update');
        Route::post('periodo/aperturar', [EstablecimientoPeriodoEvaluacionController::class, 'aperturar'])->name('periodo.aperturar');
        Route::get('configuracion-grados', [EstablecimientoPeriodoConfiguracionGradoController::class, 'index'])->name('configuracion-grados.index');
        Route::get('configuracion-grados/crear', [EstablecimientoPeriodoConfiguracionGradoController::class, 'create'])->name('configuracion-grados.create');
        Route::post('configuracion-grados', [EstablecimientoPeriodoConfiguracionGradoController::class, 'store'])->name('configuracion-grados.store');
        Route::get('configuracion-grados/{configuracionGrado}/editar', [EstablecimientoPeriodoConfiguracionGradoController::class, 'edit'])->name('configuracion-grados.edit')->whereNumber('configuracionGrado');
        Route::patch('configuracion-grados/{configuracionGrado}', [EstablecimientoPeriodoConfiguracionGradoController::class, 'update'])->name('configuracion-grados.update')->whereNumber('configuracionGrado');
        Route::delete('configuracion-grados/{configuracionGrado}', [EstablecimientoPeriodoConfiguracionGradoController::class, 'destroy'])->name('configuracion-grados.destroy')->whereNumber('configuracionGrado');
        Route::get('configuracion-asignaturas', [ConfiguracionAsignaturasController::class, 'index'])->name('configuracion-asignaturas.index');
        Route::put('configuracion-asignaturas/{aula}/ciclos', [ConfiguracionAsignaturasController::class, 'update'])->name('configuracion-asignaturas.update')->whereNumber('aula');
        Route::get('proceso-cierres', [ProcesoCierresController::class, 'index'])->name('proceso-cierres.index');
        Route::put('proceso-cierres/parciales', [ProcesoCierresController::class, 'update'])->name('proceso-cierres.update');
        Route::get('asignaturas', [EstablecimientoAsignaturaController::class, 'index'])->name('asignaturas');
        Route::get('asignaturas/{oferta}', [EstablecimientoAsignaturaController::class, 'edit'])->name('asignaturas.edit');
        Route::put('asignaturas/{oferta}', [EstablecimientoAsignaturaController::class, 'update'])->name('asignaturas.update');
        Route::get('aulas', [EstablecimientoAulaController::class, 'index'])->name('aulas');
        Route::post('aulas', [EstablecimientoAulaController::class, 'store'])->name('aulas.store');
        Route::patch('aulas/{aula}', [EstablecimientoAulaController::class, 'update'])->name('aulas.update')->whereNumber('aula');
        Route::patch('aulas/{aula}/docentes', [EstablecimientoAulaController::class, 'updateDocentes'])->name('aulas.docentes.update')->whereNumber('aula');
        Route::get('aulas/{aula}/horario', [HorarioClaseController::class, 'show'])->name('aulas.horario')->whereNumber('aula');
        Route::post('aulas/{aula}/horario', [HorarioClaseController::class, 'store'])->name('aulas.horario.store')->whereNumber('aula');
        Route::delete('aulas/{aula}/horario/{horario}', [HorarioClaseController::class, 'destroy'])->name('aulas.horario.destroy')->whereNumber('aula')->whereNumber('horario');
        Route::delete('aulas/{aula}', [EstablecimientoAulaController::class, 'destroy'])->name('aulas.destroy')->whereNumber('aula');
        Route::get('empleados', [EmpleadoController::class, 'index'])->name('empleados');
        Route::get('empleados/crear', [EmpleadoController::class, 'create'])->name('empleados.create');
        Route::post('empleados', [EmpleadoController::class, 'store'])->name('empleados.store');
        Route::get('empleados/{empleado}/editar', [EmpleadoController::class, 'edit'])->name('empleados.edit');
        Route::patch('empleados/{empleado}', [EmpleadoController::class, 'update'])->name('empleados.update');
        Route::delete('empleados/{empleado}', [EmpleadoController::class, 'destroy'])->name('empleados.destroy');
        Route::get('docentes', [DocenteController::class, 'index'])->name('docentes');
        Route::get('docentes/{empleado}/areas', [DocenteController::class, 'areas'])->name('docentes.areas');
        Route::patch('docentes/{empleado}', [DocenteController::class, 'update'])->name('docentes.update');

    });
