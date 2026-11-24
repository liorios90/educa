<?php

use App\Enums\CalificacionDestreza;
use App\Enums\Role;
use App\Enums\TipoCalificacion;
use App\Models\Alumno;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoHabilidadCivica;
use App\Models\EstablecimientoIndicadorCalificacion;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Destreza;
use App\Models\Sys_Grado;
use App\Models\Sys_IndicadorEvaluacion;
use App\Models\Sys_Subnivel;
use App\Models\User;
use App\Services\AcompanamientoCivica;
use App\Services\ConsolidadoDestrezas;

it('redirects guests from the civic skills settings', function () {
    $this->get(route('Admin.habilidades-civica.edit'))
        ->assertRedirect(route('login'));
});

it('forbids a teacher from choosing the civic skills', function () {
    $docente = assignRole(User::factory()->create([
        'establecimiento_id' => Establecimiento::factory()->create()->id,
    ]), Role::Docente);

    $this->actingAs($docente)
        ->get(route('Admin.habilidades-civica.edit'))
        ->assertForbidden();
});

it('lets an administrator save the five skills the ministry lets the school choose', function () {
    $establecimiento = Establecimiento::factory()->create(['nombre' => 'Unidad Educativa Sis Educa']);
    $admin = adminOf($establecimiento);

    $this->actingAs($admin)
        ->get(route('Admin.habilidades-civica.edit'))
        ->assertOk()
        ->assertSee('Habilidades de Cívica')
        ->assertSee('Autoconocimiento')
        ->assertSee('Elige una habilidad cognitiva');

    $this->actingAs($admin)
        ->put(route('Admin.habilidades-civica.update'), [
            'habilidades' => ['CIV.03', 'CIV.06', 'CIV.09', 'CIV.12', 'CIV.14'],
        ])
        ->assertRedirect(route('Admin.habilidades-civica.edit'))
        ->assertSessionHas('status', 'habilidades-civica-updated');

    expect(app(AcompanamientoCivica::class)->codigosElegidos($establecimiento->id))
        ->toBe(['CIV.03', 'CIV.06', 'CIV.09', 'CIV.12', 'CIV.14']);
});

it('rejects a civic selection that does not follow the ministry mix', function () {
    $admin = adminOf(Establecimiento::factory()->create());

    $this->actingAs($admin)
        ->from(route('Admin.habilidades-civica.edit'))
        ->put(route('Admin.habilidades-civica.update'), [
            'habilidades' => ['CIV.03', 'CIV.05', 'CIV.06', 'CIV.09', 'CIV.14'],
        ])
        ->assertRedirect(route('Admin.habilidades-civica.edit'))
        ->assertSessionHasErrors('habilidades');

    expect(EstablecimientoHabilidadCivica::query()->count())->toBe(0);
});

it('keeps each civic skill letter and leaves the period out of the numeric grade', function () {
    $subnivel = Sys_Subnivel::factory()->create([
        'equivale_nota_nacional' => true,
        'usa_ambitos' => false,
        'califica_por_destrezas' => true,
    ]);
    $grado = Sys_Grado::factory()->for($subnivel, 'subnivel')->create(['nombre' => 'Octavo de EGB']);
    $establecimiento = Establecimiento::factory()->create();
    $establecimientoGrado = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'grado_id' => $grado->id,
        'subnivel_id' => $subnivel->id,
    ]);
    $catalogo = Sys_Asignatura::factory()->create([
        'nombre' => AcompanamientoCivica::ASIGNATURA,
    ]);
    $malla = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $establecimientoGrado->establecimiento_modalidad_jornada_id,
        'grado_id' => $grado->id,
        'asignatura_id' => $catalogo->id,
        'califica_por_destrezas' => true,
        'tipo_calificacion' => TipoCalificacion::Cualitativa,
    ]);
    $aula = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_grado_id' => $establecimientoGrado->id,
    ]);
    $ciclo = EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $aula->establecimiento_periodo_id,
    ]);
    $alumno = Alumno::factory()->create();
    $civica = new AcompanamientoCivica;
    $civica->guardar($establecimiento, ['CIV.03', 'CIV.06', 'CIV.09', 'CIV.12', 'CIV.14']);

    $alcanzada = habilidadCivica($catalogo, $grado, 'CIV.01');
    $enProceso = habilidadCivica($catalogo, $grado, 'CIV.02');
    $noElegida = habilidadCivica($catalogo, $grado, 'CIV.05');

    calificarHabilidadCivica($alumno, $aula, $malla, $ciclo, $alcanzada, CalificacionDestreza::Alcanzado);
    calificarHabilidadCivica($alumno, $aula, $malla, $ciclo, $enProceso, CalificacionDestreza::EnProceso);
    calificarHabilidadCivica($alumno, $aula, $malla, $ciclo, $noElegida, CalificacionDestreza::Iniciado);

    $mapa = app(ConsolidadoDestrezas::class)->mapa(
        $aula,
        collect([$malla]),
        collect([$ciclo]),
        collect([$alumno]),
    );
    $resumen = $mapa[$alumno->id][$malla->id];
    $detalle = collect($resumen['detalle'])->keyBy('codigo');

    expect($resumen['ciclos'][$ciclo->id]['letra'])->toBeNull()
        ->and($resumen['ciclos'][$ciclo->id]['nota'])->toBeNull()
        ->and($detalle->keys()->all())->toBe(['CIV.01', 'CIV.02'])
        ->and($detalle['CIV.01']['ciclos'][$ciclo->id])->toBe('A')
        ->and($detalle['CIV.02']['ciclos'][$ciclo->id])->toBe('EP')
        ->and($detalle->has('CIV.05'))->toBeFalse();
});

function habilidadCivica(Sys_Asignatura $asignatura, Sys_Grado $grado, string $codigo): Sys_Destreza
{
    $destreza = Sys_Destreza::factory()->create([
        'asignatura_id' => $asignatura->id,
        'grado_id' => $grado->id,
        'codigo' => $codigo,
        'descripcion' => 'Habilidad '.$codigo,
        'orden' => (int) substr($codigo, 4),
    ]);
    Sys_IndicadorEvaluacion::factory()->create([
        'destreza_id' => $destreza->id,
        'codigo' => 'I.'.$codigo,
        'descripcion' => 'Logro '.$codigo,
    ]);

    return $destreza->load('indicadores');
}

function calificarHabilidadCivica(
    Alumno $alumno,
    EstablecimientoAula $aula,
    EstablecimientoAsignatura $malla,
    EstablecimientoPeriodoCiclo $ciclo,
    Sys_Destreza $destreza,
    CalificacionDestreza $calificacion,
): void {
    EstablecimientoIndicadorCalificacion::factory()->create([
        'establecimiento_id' => $aula->establecimiento_id,
        'alumno_id' => $alumno->id,
        'establecimiento_aula_id' => $aula->id,
        'establecimiento_asignatura_id' => $malla->id,
        'establecimiento_periodo_ciclo_id' => $ciclo->id,
        'indicador_evaluacion_id' => $destreza->indicadores->first()->id,
        'calificacion' => $calificacion,
    ]);
}
