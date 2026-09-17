<?php

use App\Models\Alumno;
use App\Models\Padre;
use App\Models\Persona;

it('belongs to a persona', function () {
    $persona = Persona::factory()->create();
    $alumno = Alumno::factory()->for($persona)->create();

    expect($alumno->persona->is($persona))->toBeTrue();
});

it('belongs to an optional padre', function () {
    $padre = Padre::factory()->create();
    $alumno = Alumno::factory()->for($padre, 'padre')->create();

    expect($alumno->padre->is($padre))->toBeTrue();
});

it('is listed among the alumnos of a persona', function () {
    $persona = Persona::factory()->create();
    $alumno = Alumno::factory()->for($persona)->create();

    expect($persona->alumnos)->toHaveCount(1);
    expect($persona->alumnos->first()->is($alumno))->toBeTrue();
});
