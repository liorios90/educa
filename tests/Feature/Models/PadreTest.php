<?php

use App\Models\Alumno;
use App\Models\Padre;
use App\Models\Persona;

it('belongs to a persona', function () {
    $persona = Persona::factory()->create();
    $padre = Padre::factory()->for($persona)->create();

    expect($padre->persona->is($persona))->toBeTrue();
});

it('is listed among the padres of a persona', function () {
    $persona = Persona::factory()->create();
    $padre = Padre::factory()->for($persona)->create();

    expect($persona->padres)->toHaveCount(1);
    expect($persona->padres->first()->is($padre))->toBeTrue();
});

it('lists assigned alumnos', function () {
    $padre = Padre::factory()->create();
    $alumno = Alumno::factory()->for($padre, 'padre')->create();

    expect($padre->alumnos)->toHaveCount(1);
    expect($padre->alumnos->first()->is($alumno))->toBeTrue();
});
