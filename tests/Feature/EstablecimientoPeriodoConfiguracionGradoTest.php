<?php

use App\Enums\TipoCalificacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\EstablecimientoPeriodoConfiguracionGrado;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Grado;

function configuracionGradoPreparada(array $atributosPeriodo = []): array
{
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta, [
        'porcentaje_examen_final' => '40.00',
        'porcentaje_proyecto_final' => '0.00',
        ...$atributosPeriodo,
    ]);
    $grado = Sys_Grado::factory()->create(['nombre' => 'Primero de Bachillerato']);
    $asignatura = Sys_Asignatura::factory()->create(['nombre' => 'Matemática']);
    $malla = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $grado->id,
        'asignatura_id' => $asignatura->id,
        'tipo_calificacion' => TipoCalificacion::Cuantitativa,
    ]);

    EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'orden' => 1,
        'nombre' => 'Primer trimestre',
        'porcentaje' => '30.00',
    ]);
    EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'orden' => 2,
        'nombre' => 'Segundo trimestre',
        'porcentaje' => '30.00',
    ]);

    return compact('establecimiento', 'admin', 'oferta', 'periodo', 'grado', 'malla');
}

function datosConfiguracionGrado(array $datos, array $ponderaciones = ['70', '20']): array
{
    return [
        'nombre' => 'Bachillerato trimestral',
        'grados' => [(string) $datos['grado']->id],
        'ponderaciones_periodo' => [1 => '30', 2 => '30'],
        'porcentaje_examen_final' => '40',
        'porcentaje_proyecto_final' => '0',
        'ciclos' => [
            1 => ['porcentaje_insumos' => $ponderaciones[0], 'porcentaje_examen' => $ponderaciones[1], 'porcentaje_proyecto' => '10'],
            2 => ['porcentaje_insumos' => '60', 'porcentaje_examen' => '30', 'porcentaje_proyecto' => '10'],
        ],
    ];
}

it('lists and creates named cycle configurations for quantitative grades', function () {
    $datos = configuracionGradoPreparada();
    $sesion = activeContextSession($datos['oferta'], $datos['periodo']);
    $gradoDestrezas = Sys_Grado::factory()->create(['nombre' => 'Cuarto EGB']);
    EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $datos['establecimiento']->id,
        'establecimiento_modalidad_jornada_id' => $datos['oferta']->id,
        'grado_id' => $gradoDestrezas->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Lengua por destrezas'])->id,
        'tipo_calificacion' => TipoCalificacion::Cualitativa,
        'califica_por_destrezas' => true,
    ]);

    $this->actingAs($datos['admin'])
        ->withSession($sesion)
        ->get(route('Admin.configuracion-grados.index'))
        ->assertOk()
        ->assertSee('Configuraciones creadas')
        ->assertSee('Nueva configuración');

    $this->actingAs($datos['admin'])
        ->withSession($sesion)
        ->get(route('Admin.configuracion-grados.create'))
        ->assertOk()
        ->assertSee('Primero de Bachillerato')
        ->assertSee('Primer trimestre')
        ->assertSee('Segundo trimestre')
        ->assertSee('Porcentaje de insumos')
        ->assertSee('Porcentaje de examen')
        ->assertSee('Porcentaje de proyecto')
        ->assertSee('Configuración del periodo')
        ->assertSee('Configuración de ciclos')
        ->assertSee('Ponderación de los ciclos')
        ->assertSee('Ponderación final')
        ->assertDontSee('Matemática')
        ->assertDontSee('Cuarto EGB')
        ->assertDontSee('Lengua por destrezas');

    $this->actingAs($datos['admin'])
        ->withSession($sesion)
        ->post(route('Admin.configuracion-grados.store'), datosConfiguracionGrado($datos))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $configuracion = EstablecimientoPeriodoConfiguracionGrado::query()->firstOrFail();

    $this->actingAs($datos['admin'])
        ->withSession($sesion)
        ->get(route('Admin.configuracion-grados.edit', $configuracion))
        ->assertOk()
        ->assertSee('Bachillerato trimestral');

    expect($configuracion->nombre)->toBe('Bachillerato trimestral')
        ->and($configuracion->grados()->whereKey($datos['grado']->id)->exists())->toBeTrue()
        ->and($configuracion->ciclos()->orderBy('ciclo_orden')->pluck('porcentaje_insumos')->all())->toBe(['70.00', '60.00'])
        ->and($configuracion->ciclos()->orderBy('ciclo_orden')->pluck('porcentaje_examen')->all())->toBe(['20.00', '30.00'])
        ->and($configuracion->ciclos()->orderBy('ciclo_orden')->pluck('porcentaje_proyecto')->all())->toBe(['10.00', '10.00'])
        ->and($configuracion->porcentaje_examen_final)->toBe('40.00')
        ->and($configuracion->porcentaje_proyecto_final)->toBe('0.00')
        ->and($configuracion->ciclos()->orderBy('ciclo_orden')->pluck('porcentaje_periodo')->all())->toBe(['30.00', '30.00']);

    $payload = datosConfiguracionGrado($datos, ['50', '30']);
    $payload['ciclos'][1]['porcentaje_proyecto'] = '20';
    $payload['ciclos'][2] = ['porcentaje_insumos' => '40', 'porcentaje_examen' => '40', 'porcentaje_proyecto' => '20'];
    $payload['nombre'] = 'Bachillerato actualizado';
    $payload['ponderaciones_periodo'] = [1 => '25', 2 => '35'];
    $payload['porcentaje_examen_final'] = '40';

    $this->actingAs($datos['admin'])
        ->withSession($sesion)
        ->patch(route('Admin.configuracion-grados.update', $configuracion), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($configuracion->fresh()->nombre)->toBe('Bachillerato actualizado')
        ->and($configuracion->fresh()->ciclos()->orderBy('ciclo_orden')->pluck('porcentaje_insumos')->all())->toBe(['50.00', '40.00'])
        ->and($configuracion->fresh()->porcentaje_examen_final)->toBe('40.00')
        ->and($configuracion->fresh()->ciclos()->orderBy('ciclo_orden')->pluck('porcentaje_periodo')->all())->toBe(['25.00', '35.00']);

    $this->actingAs($datos['admin'])
        ->withSession($sesion)
        ->get(route('Admin.configuracion-grados.index'))
        ->assertSee('Bachillerato actualizado')
        ->assertSee('Primero de Bachillerato');
});

it('rejects cycle component weights that do not add up to 100 percent', function () {
    $datos = configuracionGradoPreparada();

    $this->actingAs($datos['admin'])
        ->withSession(activeContextSession($datos['oferta'], $datos['periodo']))
        ->from(route('Admin.configuracion-grados.create'))
        ->post(route('Admin.configuracion-grados.store'), datosConfiguracionGrado($datos, ['20', '30']))
        ->assertSessionHasErrors('ciclos.1');

    expect(EstablecimientoPeriodoConfiguracionGrado::query()->count())->toBe(0);
});

it('prevents a grade from belonging to multiple configurations in the same period', function () {
    $datos = configuracionGradoPreparada();
    $sesion = activeContextSession($datos['oferta'], $datos['periodo']);

    $this->actingAs($datos['admin'])
        ->withSession($sesion)
        ->post(route('Admin.configuracion-grados.store'), datosConfiguracionGrado($datos))
        ->assertSessionHasNoErrors();

    $payload = datosConfiguracionGrado($datos);
    $payload['nombre'] = 'Otro nombre';

    $this->actingAs($datos['admin'])
        ->withSession($sesion)
        ->from(route('Admin.configuracion-grados.create'))
        ->post(route('Admin.configuracion-grados.store'), $payload)
        ->assertSessionHasErrors('grados');

    expect(EstablecimientoPeriodoConfiguracionGrado::query()->count())->toBe(1);
});
