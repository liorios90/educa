<?php

use App\Models\EstablecimientoMatricula;
use Database\Seeders\MatriculasGruposFlotantesSeeder;

it('matricula cuatro alumnos en dos paralelos del mismo grado y puede repetirse', function () {
    $curso = cursoDeOctavo();
    $curso['establecimiento']->update(['codigo_amie' => '17HDEMO01']);
    $curso['periodo']->update(['nombre' => 'Periodo demostrativo 2026-2027']);

    $this->seed(MatriculasGruposFlotantesSeeder::class);
    $this->seed(MatriculasGruposFlotantesSeeder::class);

    $matriculas = EstablecimientoMatricula::query()
        ->where('establecimiento_periodo_id', $curso['periodo']->id)
        ->with('aula')
        ->get();

    expect($matriculas)->toHaveCount(4)
        ->and($matriculas->where('establecimiento_aula_id', $curso['aulaA']->id))->toHaveCount(2)
        ->and($matriculas->where('establecimiento_aula_id', $curso['aulaB']->id))->toHaveCount(2);
});
