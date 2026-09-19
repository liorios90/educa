<?php

use App\Models\Empleado;
use App\Models\Persona;
use App\Models\Sys_Funcion;
use App\Models\Sys_TipoContrato;

it('belongs to a persona', function () {
    $persona = Persona::factory()->create();
    $empleado = Empleado::factory()->for($persona)->create();

    expect($empleado->persona->is($persona))->toBeTrue();
});

it('belongs to a tipo de contrato', function () {
    $tipoContrato = Sys_TipoContrato::factory()->create();
    $empleado = Empleado::factory()->for($tipoContrato, 'tipoContrato')->create();

    expect($empleado->tipoContrato->is($tipoContrato))->toBeTrue();
});

it('belongs to an optional funcion', function () {
    $funcion = Sys_Funcion::factory()->create();
    $empleado = Empleado::factory()->for($funcion, 'funcion')->create();

    expect($empleado->funcion->is($funcion))->toBeTrue();
});

it('is listed among the empleados of a persona', function () {
    $persona = Persona::factory()->create();
    $empleado = Empleado::factory()->for($persona)->create();

    expect($persona->empleados)->toHaveCount(1);
    expect($persona->empleados->first()->is($empleado))->toBeTrue();
});

it('is listed among the empleados of a tipo de contrato', function () {
    $tipoContrato = Sys_TipoContrato::factory()->create();
    $empleado = Empleado::factory()->for($tipoContrato, 'tipoContrato')->create();

    expect($tipoContrato->empleados)->toHaveCount(1);
    expect($tipoContrato->empleados->first()->is($empleado))->toBeTrue();
});

it('is listed among the empleados of a funcion', function () {
    $funcion = Sys_Funcion::factory()->create();
    $empleado = Empleado::factory()->for($funcion, 'funcion')->create();

    expect($funcion->empleados)->toHaveCount(1);
    expect($funcion->empleados->first()->is($empleado))->toBeTrue();
});
