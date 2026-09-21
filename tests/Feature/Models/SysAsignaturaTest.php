<?php

use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;

it('belongs to an area', function () {
    $area = Sys_Area::factory()->create();
    $asignatura = Sys_Asignatura::factory()->for($area, 'area')->create();

    expect($asignatura->area->is($area))->toBeTrue()
        ->and($area->asignaturas->contains($asignatura))->toBeTrue();
});
