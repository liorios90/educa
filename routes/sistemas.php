<?php

use App\Crud\CrudRegistry;
use App\Http\Controllers\CurriculoController;
use App\Http\Controllers\EstablecimientoController;
use App\Http\Controllers\EstructuraController;
use App\Http\Controllers\GenericCrudController;
use App\Http\Controllers\NavigationItemController;
use App\Http\Controllers\NavigationSubmenuController;
use App\Http\Controllers\ReportDefinitionController;
use App\Http\Controllers\SistemasController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:Sistemas'])
    ->prefix('sistemas')
    ->name('sistemas.')
    ->group(function () {
        Route::view('/', 'sistemas.home')->name('home');
        Route::get('/usuarios', [UserController::class, 'index'])->name('users');
        Route::get('/usuarios/crear', [UserController::class, 'create'])->name('users.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
        Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])->name('users.edit');
        Route::patch('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('/reportes', [ReportDefinitionController::class, 'index'])->name('reports.index');
        Route::get('/reportes/crear', [ReportDefinitionController::class, 'create'])->name('reports.create');
        Route::post('/reportes', [ReportDefinitionController::class, 'store'])->name('reports.store');
        Route::get('/reportes/{reportDefinition}/editar', [ReportDefinitionController::class, 'edit'])->name('reports.edit');
        Route::patch('/reportes/{reportDefinition}', [ReportDefinitionController::class, 'update'])->name('reports.update');
        Route::delete('/reportes/{reportDefinition}', [ReportDefinitionController::class, 'destroy'])->name('reports.destroy');
        Route::get('/establecimientos', [EstablecimientoController::class, 'index'])->name('establecimientos');
        Route::get('/establecimientos/crear', [EstablecimientoController::class, 'create'])->name('establecimientos.create');
        Route::post('/establecimientos', [EstablecimientoController::class, 'store'])->name('establecimientos.store');
        Route::get('/establecimientos/{establecimiento}/editar', [EstablecimientoController::class, 'edit'])->name('establecimientos.edit');
        Route::patch('/establecimientos/{establecimiento}', [EstablecimientoController::class, 'update'])->name('establecimientos.update');
        Route::delete('/establecimientos/{establecimiento}', [EstablecimientoController::class, 'destroy'])->name('establecimientos.destroy');
        Route::get('/estructura', [EstructuraController::class, 'index'])->name('estructura');
        Route::post('/estructura/niveles', [EstructuraController::class, 'storeNivel'])->name('estructura.niveles.store');
        Route::patch('/estructura/niveles/{nivel}', [EstructuraController::class, 'updateNivel'])->name('estructura.niveles.update')->whereNumber('nivel');
        Route::delete('/estructura/niveles/{nivel}', [EstructuraController::class, 'destroyNivel'])->name('estructura.niveles.destroy')->whereNumber('nivel');
        Route::post('/estructura/niveles/{nivel}/subniveles', [EstructuraController::class, 'storeSubnivel'])->name('estructura.subniveles.store')->whereNumber('nivel');
        Route::patch('/estructura/niveles/{nivel}/subniveles/{subnivel}', [EstructuraController::class, 'updateSubnivel'])->name('estructura.subniveles.update')->scopeBindings()->whereNumber('nivel')->whereNumber('subnivel');
        Route::delete('/estructura/niveles/{nivel}/subniveles/{subnivel}', [EstructuraController::class, 'destroySubnivel'])->name('estructura.subniveles.destroy')->scopeBindings()->whereNumber('nivel')->whereNumber('subnivel');
        Route::post('/estructura/niveles/{nivel}/subniveles/{subnivel}/grados', [EstructuraController::class, 'storeGrado'])->name('estructura.grados.store')->scopeBindings()->whereNumber('nivel')->whereNumber('subnivel');
        Route::patch('/estructura/niveles/{nivel}/subniveles/{subnivel}/grados/{grado}', [EstructuraController::class, 'updateGrado'])->name('estructura.grados.update')->scopeBindings()->whereNumber('nivel')->whereNumber('subnivel')->whereNumber('grado');
        Route::delete('/estructura/niveles/{nivel}/subniveles/{subnivel}/grados/{grado}', [EstructuraController::class, 'destroyGrado'])->name('estructura.grados.destroy')->scopeBindings()->whereNumber('nivel')->whereNumber('subnivel')->whereNumber('grado');
        Route::get('/curriculo', [CurriculoController::class, 'index'])->name('curriculo');
        Route::post('/curriculo/niveles/{nivel}/subniveles/{subnivel}/areas', [CurriculoController::class, 'storeArea'])->name('curriculo.areas.store')->scopeBindings()->whereNumber('nivel')->whereNumber('subnivel');
        Route::patch('/curriculo/niveles/{nivel}/subniveles/{subnivel}/areas/{area}', [CurriculoController::class, 'updateArea'])->name('curriculo.areas.update')->scopeBindings()->whereNumber('nivel')->whereNumber('subnivel')->whereNumber('area');
        Route::delete('/curriculo/niveles/{nivel}/subniveles/{subnivel}/areas/{area}', [CurriculoController::class, 'destroyArea'])->name('curriculo.areas.destroy')->scopeBindings()->whereNumber('nivel')->whereNumber('subnivel')->whereNumber('area');
        Route::post('/curriculo/niveles/{nivel}/subniveles/{subnivel}/areas/{area}/asignaturas', [CurriculoController::class, 'storeAsignatura'])->name('curriculo.asignaturas.store')->scopeBindings()->whereNumber('nivel')->whereNumber('subnivel')->whereNumber('area');
        Route::patch('/curriculo/niveles/{nivel}/subniveles/{subnivel}/areas/{area}/asignaturas/{asignatura}', [CurriculoController::class, 'updateAsignatura'])->name('curriculo.asignaturas.update')->scopeBindings()->whereNumber('nivel')->whereNumber('subnivel')->whereNumber('area')->whereNumber('asignatura');
        Route::delete('/curriculo/niveles/{nivel}/subniveles/{subnivel}/areas/{area}/asignaturas/{asignatura}', [CurriculoController::class, 'destroyAsignatura'])->name('curriculo.asignaturas.destroy')->scopeBindings()->whereNumber('nivel')->whereNumber('subnivel')->whereNumber('area')->whereNumber('asignatura');
        Route::get('/prueba', [SistemasController::class, 'prueba'])->name('prueba');
        Route::get('/prueba', [SistemasController::class, 'prueba2'])->name('prueba2');
        Route::get('/menu', [NavigationItemController::class, 'index'])->name('navigation-items.index');
        Route::get('/menu/crear', [NavigationItemController::class, 'create'])->name('navigation-items.create');
        Route::post('/menu', [NavigationItemController::class, 'store'])->name('navigation-items.store');
        Route::get('/menu/{navigationItem}/editar', [NavigationItemController::class, 'edit'])->name('navigation-items.edit');
        Route::patch('/menu/{navigationItem}', [NavigationItemController::class, 'update'])->name('navigation-items.update');
        Route::delete('/menu/{navigationItem}', [NavigationItemController::class, 'destroy'])->name('navigation-items.destroy');
        Route::get('/menu/{navigationItem}/submenus', [NavigationSubmenuController::class, 'index'])->name('navigation-items.submenus.index');
        Route::get('/menu/{navigationItem}/submenus/crear', [NavigationSubmenuController::class, 'create'])->name('navigation-items.submenus.create');
        Route::post('/menu/{navigationItem}/submenus', [NavigationSubmenuController::class, 'store'])->name('navigation-items.submenus.store');
        Route::get('/menu/{navigationItem}/submenus/{submenu}/editar', [NavigationSubmenuController::class, 'edit'])->name('navigation-items.submenus.edit')->whereNumber('submenu');
        Route::patch('/menu/{navigationItem}/submenus/{submenu}', [NavigationSubmenuController::class, 'update'])->name('navigation-items.submenus.update')->whereNumber('submenu');
        Route::delete('/menu/{navigationItem}/submenus/{submenu}', [NavigationSubmenuController::class, 'destroy'])->name('navigation-items.submenus.destroy')->whereNumber('submenu');

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
