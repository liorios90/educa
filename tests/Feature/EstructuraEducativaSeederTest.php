<?php

use App\Enums\TipoCalificacion;
use App\Models\Sys_Grado;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use Database\Seeders\EstructuraEducativaSeeder;

it('seeds the ecuadorian escolarizada structure from the LOEI', function () {
    $this->seed(EstructuraEducativaSeeder::class);

    expect(Sys_Nivel::query()->orderBy('id')->pluck('nombre')->all())->toBe([
        'Educación Inicial',
        'Educación General Básica',
        'Bachillerato',
    ]);

    $egb = Sys_Nivel::query()->where('nombre', 'Educación General Básica')->firstOrFail();

    expect($egb->subniveles()->pluck('nombre')->all())->toBe([
        'Preparatoria',
        'Básica Elemental',
        'Básica Media',
        'Básica Superior',
    ]);

    $preparatoria = Sys_Subnivel::query()->where('nombre', 'Preparatoria')->firstOrFail();
    $elemental = Sys_Subnivel::query()->where('nombre', 'Básica Elemental')->firstOrFail();
    $bachillerato = Sys_Subnivel::query()->where('nombre', 'Bachillerato General Unificado')->firstOrFail();

    expect($preparatoria->tipo_calificacion)->toBe(TipoCalificacion::Destrezas)
        ->and($elemental->tipo_calificacion)->toBe(TipoCalificacion::Calificacion)
        ->and($preparatoria->grados()->pluck('nombre')->all())->toBe(['Primero de EGB'])
        ->and($elemental->grados()->pluck('nombre')->all())->toBe([
            'Segundo de EGB',
            'Tercero de EGB',
            'Cuarto de EGB',
        ])
        ->and($bachillerato->grados()->pluck('nombre')->all())->toBe([
            'Primero de Bachillerato',
            'Segundo de Bachillerato',
            'Tercero de Bachillerato',
        ])
        ->and(Sys_Grado::query()->count())->toBe(15);
});
