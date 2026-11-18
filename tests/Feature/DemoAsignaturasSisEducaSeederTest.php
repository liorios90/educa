<?php

use App\Enums\ModoLibretaArea;
use App\Enums\TipoAsignatura;
use App\Enums\TipoCalificacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAreaLibreta;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoGrado;
use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Grado;
use Database\Seeders\DemoAsignaturasSisEducaSeeder;

it('selecciona todas las asignaturas del catálogo en los grados ofertados de Sis Educa', function () {
    $establecimiento = Establecimiento::factory()->create(['codigo_amie' => '01AS01061']);
    $oferta = ofertaDe($establecimiento);
    $elemental = catalogoEstructura([
        'nivel' => 'Educación General Básica',
        'subnivel' => 'Básica Elemental',
        'grado' => 'Segundo de EGB',
    ]);
    $elemental['subnivel']->update([
        'tipo_calificacion' => TipoCalificacion::Cualitativa,
        'califica_por_destrezas' => true,
    ]);
    $tercero = Sys_Grado::factory()->for($elemental['subnivel'], 'subnivel')->create(['nombre' => 'Tercero de EGB']);
    foreach ([$elemental['grado'], $tercero] as $grado) {
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $elemental['subnivel']->id,
            'grado_id' => $grado->id,
        ]);
    }

    $area = Sys_Area::factory()->for($elemental['subnivel'], 'subnivel')->create([
        'nombre' => 'Ciencias Naturales',
        'es_tecnica' => false,
    ]);
    $matematica = Sys_Asignatura::factory()->for($area, 'area')->create([
        'nombre' => 'Ciencias Naturales',
        'horas_semanales' => 3,
        'aparece_en_libreta' => true,
    ]);
    $optativa = Sys_Asignatura::factory()->for($area, 'area')->create([
        'nombre' => 'Laboratorio',
        'horas_semanales' => 2,
        'aparece_en_libreta' => false,
    ]);
    $matematica->gradosCurriculares()->attach($elemental['grado']->id, ['obligatoria' => true]);
    $optativa->gradosCurriculares()->attach($elemental['grado']->id, ['obligatoria' => false]);
    $tecnica = Sys_Asignatura::factory()->for(
        Sys_Area::factory()->for($elemental['subnivel'], 'subnivel')->create(['es_tecnica' => true]),
        'area',
    )->create(['nombre' => 'Módulo técnico']);
    $media = catalogoEstructura([
        'subnivel' => 'Básica Media',
        'grado' => 'Quinto de EGB',
    ]);
    $ajena = Sys_Asignatura::factory()->for(
        Sys_Area::factory()->for($media['subnivel'], 'subnivel')->create(['nombre' => 'Matemática']),
        'area',
    )->create(['nombre' => 'Matemática']);

    $this->seed(DemoAsignaturasSisEducaSeeder::class);
    $this->seed(DemoAsignaturasSisEducaSeeder::class);

    $malla = EstablecimientoAsignatura::query()
        ->where('establecimiento_modalidad_jornada_id', $oferta->id)
        ->get();

    expect($malla)->toHaveCount(4)
        ->and($malla->pluck('asignatura_id')->all())->not->toContain($tecnica->id)
        ->and($malla->pluck('asignatura_id')->all())->not->toContain($ajena->id);

    $ciencias = $malla->firstWhere('asignatura_id', $matematica->id);

    expect($ciencias?->tipo_asignatura)->toBe(TipoAsignatura::TroncoComun)
        ->and($ciencias?->tipo_calificacion)->toBe(TipoCalificacion::Cualitativa)
        ->and($ciencias?->califica_por_destrezas)->toBeTrue()
        ->and($ciencias?->horas_semanales)->toBe(3)
        ->and($malla->firstWhere('asignatura_id', $optativa->id)?->tipo_asignatura)->toBe(TipoAsignatura::Optativa)
        ->and($malla->firstWhere('asignatura_id', $optativa->id)?->aparece_en_libreta)->toBeFalse()
        ->and(EstablecimientoAreaLibreta::query()->where('establecimiento_modalidad_jornada_id', $oferta->id)->where('area_id', $area->id)->count())->toBe(2)
        ->and(EstablecimientoAreaLibreta::query()->where('area_id', $area->id)->first()?->modo_libreta)->toBe(ModoLibretaArea::Asignaturas);
});
