<?php

use App\Enums\TipoCalificacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaAsignaturaCiclo;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Grado;
use Database\Seeders\NavigationSeeder;

it('lists only aulas without destrezas and opens their quantitative subjects', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $gradoCuantitativo = Sys_Grado::factory()->create(['nombre' => 'Primero de Bachillerato']);
    $gradoEstablecimiento = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $gradoCuantitativo->id,
    ]);
    $aulaCuantitativa = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $gradoEstablecimiento->id,
        'paralelo' => 'A',
    ]);

    EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $gradoCuantitativo->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Matemática'])->id,
        'tipo_calificacion' => TipoCalificacion::Cuantitativa,
    ]);
    EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $gradoCuantitativo->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Comportamiento'])->id,
        'tipo_calificacion' => TipoCalificacion::Cualitativa,
    ]);

    $gradoDestrezas = Sys_Grado::factory()->create(['nombre' => 'Cuarto EGB']);
    $gradoDestrezasEstablecimiento = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $gradoDestrezas->id,
    ]);
    EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $gradoDestrezasEstablecimiento->id,
        'paralelo' => 'B',
    ]);
    EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $gradoDestrezas->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Lengua por destrezas'])->id,
        'tipo_calificacion' => TipoCalificacion::Cualitativa,
        'califica_por_destrezas' => true,
    ]);

    $this->seed(NavigationSeeder::class);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->get(route('Admin.configuracion-asignaturas.index'))
        ->assertOk()
        ->assertSee('Por asignaturas')
        ->assertSee('Primero de Bachillerato A')
        ->assertSee('Matemática')
        ->assertDontSee('Cuarto EGB')
        ->assertDontSee('Lengua por destrezas')
        ->assertDontSee('Comportamiento')
        ->assertSee('dispatch(\'aula-asignaturas\'', false)
        ->assertSee('Asignaturas');
});

it('requires an administrator role to access the subject configuration page', function () {
    $this->get(route('Admin.configuracion-asignaturas.index'))->assertRedirect(route('login'));
});

it('shows quantitative grades even when they do not have aulas created yet', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $grado = Sys_Grado::factory()->create(['nombre' => 'Primero de Bachillerato']);
    EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $grado->id,
    ]);
    EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $grado->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Matemática'])->id,
        'tipo_calificacion' => TipoCalificacion::Cuantitativa,
    ]);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->get(route('Admin.configuracion-asignaturas.index'))
        ->assertOk()
        ->assertSee('Primero de Bachillerato')
        ->assertSee('Aún no se ha creado un aula para este grado en el periodo.')
        ->assertSee('Matemática');
});

it('saves cycle component percentages separately for each quantitative subject in an aula', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $grado = Sys_Grado::factory()->create(['nombre' => 'Primero de Bachillerato']);
    $gradoEstablecimiento = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $grado->id,
    ]);
    $aula = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $gradoEstablecimiento->id,
    ]);
    $aulaParaleloB = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $gradoEstablecimiento->id,
        'paralelo' => 'B',
    ]);
    $asignatura = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $grado->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Matemática'])->id,
        'tipo_calificacion' => TipoCalificacion::Cuantitativa,
    ]);
    EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'orden' => 1,
        'nombre' => 'Primer quimestre',
        'porcentaje_insumos' => '70.00',
        'porcentaje_examen' => '20.00',
        'porcentaje_proyecto' => '10.00',
    ]);
    EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'orden' => 2,
        'nombre' => 'Segundo quimestre',
        'porcentaje_insumos' => '80.00',
        'porcentaje_examen' => '0.00',
        'porcentaje_proyecto' => '20.00',
    ]);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->put(route('Admin.configuracion-asignaturas.update', $aula), [
            'configuraciones' => [
                $asignatura->id => [
                    'personalizada' => '1',
                    'ciclos' => [
                        1 => ['porcentaje_insumos' => '60', 'porcentaje_examen' => '30', 'porcentaje_proyecto' => '10'],
                        2 => ['porcentaje_insumos' => '75', 'porcentaje_examen' => '0', 'porcentaje_proyecto' => '25'],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('Admin.configuracion-asignaturas.index'))
        ->assertSessionHas('status', 'configuracion-asignaturas-updated');

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->put(route('Admin.configuracion-asignaturas.update', $aulaParaleloB), [
            'configuraciones' => [
                $asignatura->id => [
                    'personalizada' => '1',
                    'ciclos' => [
                        1 => ['porcentaje_insumos' => '50', 'porcentaje_examen' => '40', 'porcentaje_proyecto' => '10'],
                        2 => ['porcentaje_insumos' => '60', 'porcentaje_examen' => '0', 'porcentaje_proyecto' => '40'],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('Admin.configuracion-asignaturas.index'));

    $guardados = EstablecimientoAulaAsignaturaCiclo::query()
        ->where('establecimiento_aula_id', $aula->id)
        ->orderBy('ciclo_orden')
        ->get();
    $guardadosParaleloB = EstablecimientoAulaAsignaturaCiclo::query()
        ->where('establecimiento_aula_id', $aulaParaleloB->id)
        ->orderBy('ciclo_orden')
        ->get();

    expect($guardados)->toHaveCount(2)
        ->and($guardados->pluck('porcentaje_insumos')->all())->toBe(['60.00', '75.00'])
        ->and($guardados->pluck('porcentaje_examen')->all())->toBe(['30.00', '0.00'])
        ->and($guardados->pluck('porcentaje_proyecto')->all())->toBe(['10.00', '25.00'])
        ->and($guardadosParaleloB->pluck('porcentaje_insumos')->all())->toBe(['50.00', '60.00'])
        ->and($guardadosParaleloB->pluck('porcentaje_proyecto')->all())->toBe(['10.00', '40.00']);
});

it('rejects a subject cycle whose components do not add up to one hundred percent', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $grado = Sys_Grado::factory()->create();
    $gradoEstablecimiento = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $grado->id,
    ]);
    $aula = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $gradoEstablecimiento->id,
    ]);
    $asignatura = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $grado->id,
        'tipo_calificacion' => TipoCalificacion::Cuantitativa,
    ]);
    EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'orden' => 1,
    ]);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->put(route('Admin.configuracion-asignaturas.update', $aula), [
            'configuraciones' => [
                $asignatura->id => [
                    'personalizada' => '1',
                    'ciclos' => [
                        1 => ['porcentaje_insumos' => '60', 'porcentaje_examen' => '30', 'porcentaje_proyecto' => '5'],
                    ],
                ],
            ],
        ])
        ->assertSessionHasErrors("configuraciones.{$asignatura->id}.ciclos.1");

    expect(EstablecimientoAulaAsignaturaCiclo::query()->where('establecimiento_aula_id', $aula->id)->exists())->toBeFalse();
});

it('does not allow an administrator to configure an aula from another establishment', function () {
    $establecimientoPropio = Establecimiento::factory()->create();
    $admin = adminOf($establecimientoPropio);
    $ofertaPropia = ofertaDe($establecimientoPropio);
    $periodoPropio = periodoActivoDe($ofertaPropia);

    $establecimientoAjeno = Establecimiento::factory()->create();
    $ofertaAjena = ofertaDe($establecimientoAjeno);
    $periodoAjeno = periodoActivoDe($ofertaAjena);
    $grado = Sys_Grado::factory()->create();
    $gradoAjeno = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimientoAjeno->id,
        'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
        'grado_id' => $grado->id,
    ]);
    $aulaAjena = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimientoAjeno->id,
        'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
        'establecimiento_periodo_id' => $periodoAjeno->id,
        'establecimiento_grado_id' => $gradoAjeno->id,
    ]);

    $this->actingAs($admin)
        ->withSession(activeContextSession($ofertaPropia, $periodoPropio))
        ->put(route('Admin.configuracion-asignaturas.update', $aulaAjena), [
            'configuraciones' => [999999 => ['personalizada' => '0', 'ciclos' => []]],
        ])
        ->assertNotFound();
});
