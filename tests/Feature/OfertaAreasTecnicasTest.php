<?php

use App\Models\Establecimiento;
use App\Models\EstablecimientoGrado;
use App\Models\Sys_Area;
use App\Models\Sys_Grado;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;

it('lets an establishment activate only technical areas in its offered sublevels', function () {
    $establecimiento = Establecimiento::factory()->create(['bachillerato_tecnico' => true]);
    $oferta = ofertaDe($establecimiento);
    $nivel = Sys_Nivel::factory()->create(['nombre' => 'Bachillerato']);
    $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create(['nombre' => 'BGU']);
    $grado = Sys_Grado::factory()->for($subnivel, 'subnivel')->create();
    EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'subnivel_id' => $subnivel->id,
        'grado_id' => $grado->id,
    ]);
    $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create(['es_tecnica' => true]);

    $this->actingAs(adminOf($establecimiento))
        ->put(route('Admin.asignaturas.areas-tecnicas.update', $oferta), ['areas_tecnicas' => [$area->id]])
        ->assertRedirect(route('Admin.asignaturas.edit', $oferta));

    expect($oferta->fresh()->areasTecnicas()->whereKey($area->id)->exists())->toBeTrue();
});

it('rejects activating a technical area outside the offered sublevels', function () {
    $establecimiento = Establecimiento::factory()->create(['bachillerato_tecnico' => true]);
    $oferta = ofertaDe($establecimiento);
    $nivel = Sys_Nivel::factory()->create();
    $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
    $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create(['es_tecnica' => true]);

    $this->actingAs(adminOf($establecimiento))
        ->put(route('Admin.asignaturas.areas-tecnicas.update', $oferta), ['areas_tecnicas' => [$area->id]])
        ->assertStatus(422);
});

it('forbids activating technical areas when the establishment has not enabled technical bachillerato', function () {
    $establecimiento = Establecimiento::factory()->create(['bachillerato_tecnico' => false]);
    $oferta = ofertaDe($establecimiento);
    $nivel = Sys_Nivel::factory()->create(['nombre' => 'Bachillerato']);
    $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create(['nombre' => 'BGU']);
    $grado = Sys_Grado::factory()->for($subnivel, 'subnivel')->create();
    EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'subnivel_id' => $subnivel->id,
        'grado_id' => $grado->id,
    ]);
    $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create(['es_tecnica' => true]);

    $this->actingAs(adminOf($establecimiento))
        ->put(route('Admin.asignaturas.areas-tecnicas.update', $oferta), ['areas_tecnicas' => [$area->id]])
        ->assertForbidden();
});
