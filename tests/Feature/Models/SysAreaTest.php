<?php

use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Subnivel;

it('belongs to a subnivel and has asignaturas', function () {
    $subnivel = Sys_Subnivel::factory()->create();
    $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create();
    $asignatura = Sys_Asignatura::factory()->for($area, 'area')->create();

    expect($area->subnivel->is($subnivel))->toBeTrue()
        ->and($area->asignaturas->contains($asignatura))->toBeTrue()
        ->and($subnivel->areas->contains($area))->toBeTrue()
        ->and($subnivel->asignaturas->contains($asignatura))->toBeTrue();
});
