<?php

use App\Models\Establecimiento;
use App\Models\Persona;
use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;

it('belongs to a provincia and a nationality', function () {
    $persona = Persona::factory()->create();

    expect($persona->provincia)->toBeInstanceOf(Sys_Provincia::class);
    expect($persona->nacionalidad)->toBeInstanceOf(Sys_Pais::class);
});

it('belongs to an establishment when one is assigned', function () {
    $establecimiento = Establecimiento::factory()->create();
    $persona = Persona::factory()->create([
        'establecimiento_id' => $establecimiento->id,
    ]);

    expect($persona->establecimiento->is($establecimiento))->toBeTrue();
});
