<?php

use App\Enums\CalificacionDestreza;
use App\Enums\Role;
use App\Models\Sys_CriterioEvaluacion;
use App\Models\Sys_Destreza;
use App\Models\Sys_IndicadorEvaluacion;
use App\Models\Sys_Subnivel;
use App\Models\User;
use Database\Seeders\CatalogoInicialPreparatoriaSeeder;
use Database\Seeders\CurriculoEducativoSeeder;
use Database\Seeders\EstructuraEducativaSeeder;

it('carga criterios destrezas e indicadores de inicial y preparatoria', function () {
    $this->seed(EstructuraEducativaSeeder::class);
    $this->seed(CurriculoEducativoSeeder::class);
    $this->seed(CatalogoInicialPreparatoriaSeeder::class);

    $preparatoria = Sys_Subnivel::query()->where('nombre', 'Preparatoria')->firstOrFail();
    $inicial = Sys_Subnivel::query()->where('nombre', 'Inicial 2')->firstOrFail();

    expect(Sys_CriterioEvaluacion::query()->where('codigo', 'CE.CS.1.1')->exists())->toBeTrue()
        ->and(Sys_Destreza::query()->where('codigo', 'CS.1.1.1')->where('imprescindible', true)->exists())->toBeTrue()
        ->and(Sys_IndicadorEvaluacion::query()->where('codigo', 'I.CS.1.1.1')->exists())->toBeTrue()
        ->and(Sys_Destreza::query()->where('codigo', 'EFL.1.5.4')->where('imprescindible', false)->exists())->toBeTrue()
        ->and(Sys_Destreza::query()->where('codigo', 'I2.IA.1')->exists())->toBeTrue()
        ->and($preparatoria->usa_ambitos)->toBeTrue()
        ->and($preparatoria->equivale_nota_nacional)->toBeFalse()
        ->and($inicial->usa_ambitos)->toBeTrue();

    $destreza = Sys_Destreza::query()->where('codigo', 'CS.1.1.1')->firstOrFail();

    expect($destreza->criterio?->codigo)->toBe('CE.CS.1.1')
        ->and($destreza->indicadores)->toHaveCount(1);
});

it('permite a sistemas mantener un criterio de preparatoria', function () {
    $this->seed(EstructuraEducativaSeeder::class);
    $this->seed(CurriculoEducativoSeeder::class);
    $user = assignRole(User::factory()->create(), Role::Sistemas);
    $subnivel = Sys_Subnivel::query()->where('nombre', 'Preparatoria')->firstOrFail();
    $grado = $subnivel->grados()->firstOrFail();
    $asignatura = $subnivel->asignaturas()->where('sys_asignaturas.nombre', 'Convivencia')->firstOrFail();
    $area = $asignatura->area;
    $nivel = $subnivel->nivel;

    $this->actingAs($user)
        ->post(route('sistemas.curriculo.criterios.store', [$nivel, $subnivel, $area, $asignatura]), [
            'grado_id' => $grado->id,
            'codigo' => 'CE.CS.9.9',
            'descripcion' => 'Cumple un acuerdo nuevo del aula.',
            'orden' => 4,
        ])
        ->assertRedirect();

    expect(Sys_CriterioEvaluacion::query()->where('codigo', 'CE.CS.9.9')->exists())->toBeTrue();
});

it('no promedia la letra de una destreza de preparatoria', function () {
    expect(CalificacionDestreza::APlus->banda())->toBe('Destreza o aprendizaje alcanzado')
        ->and(CalificacionDestreza::CPlus->requiereAcompanamiento())->toBeTrue()
        ->and(CalificacionDestreza::BPlus->requiereAcompanamiento())->toBeFalse()
        ->and(CalificacionDestreza::APlus->notaElemental())->toBeNull()
        ->and(CalificacionDestreza::ultimaEvaluada([
            CalificacionDestreza::EMinus,
            CalificacionDestreza::NoEvaluado,
            CalificacionDestreza::AMinus,
        ]))->toBe(CalificacionDestreza::AMinus);
});
