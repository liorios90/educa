<?php

use App\Models\Sys_Destreza;
use App\Models\Sys_Grado;
use Database\Seeders\CatalogoCivicaSeeder;
use Database\Seeders\CurriculoEducativoSeeder;
use Database\Seeders\EstructuraEducativaSeeder;

it('carga las quince habilidades de Cívica y marca cuatro como obligatorias', function () {
    $this->seed(EstructuraEducativaSeeder::class);
    $this->seed(CurriculoEducativoSeeder::class);
    $this->seed(CatalogoCivicaSeeder::class);
    $this->seed(CatalogoCivicaSeeder::class);

    $segundo = Sys_Grado::query()->where('nombre', 'Segundo de EGB')->firstOrFail();
    $primero = Sys_Grado::query()->where('nombre', 'Primero de Bachillerato')->firstOrFail();
    $elementales = Sys_Destreza::query()
        ->where('grado_id', $segundo->id)
        ->where('codigo', 'like', 'CIV.%')
        ->get();
    $autoconocimiento = $elementales->firstWhere('codigo', 'CIV.01');

    expect($elementales)->toHaveCount(15)
        ->and($elementales->where('imprescindible', true)->pluck('codigo')->sort()->values()->all())->toBe(['CIV.01', 'CIV.02', 'CIV.04', 'CIV.11'])
        ->and($autoconocimiento?->indicadores->first()?->descripcion)->toBe('Describe sus estados de ánimo, sus fortalezas y preferencias.')
        ->and($autoconocimiento?->criterio?->codigo)->toBe('CE.CIV.COG')
        ->and(Sys_Destreza::query()->where('grado_id', $primero->id)->where('codigo', 'CIV.11')->first()?->indicadores->first()?->descripcion)
        ->toBe('Reconoce y analiza las causas de los conflictos, y aplica estrategias para resolverlos de manera justa y respetuosa.');
});
