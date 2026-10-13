<?php

use App\Enums\EsquemaCiclo;
use App\Enums\Role;
use App\Enums\TipoCalificacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoPeriodoAsignaturaCiclo;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\EstablecimientoPeriodoCicloParcial;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Grado;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

describe('edit', function () {
    it('allows administrators to configure the active period', function () {
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'Unidad Educativa Andina']);
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento, ['modalidad' => 'Presencial', 'jornada' => 'Matutina']);
        $periodo = periodoActivoDe($oferta, ['nombre' => '2026-2027 Sierra']);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->get(route('Admin.periodo'))
            ->assertOk()
            ->assertSee('Periodo')
            ->assertSee('Unidad Educativa Andina')
            ->assertSee('Presencial · Matutina')
            ->assertSee('2026-2027 Sierra')
            ->assertSee('Configuración de periodo')
            ->assertSee('Configuración de ciclos')
            ->assertDontSee('Configuración por Grados')
            ->assertSee('Número de ciclos')
            ->assertSee('Con 2 el nombre es Quimestre y con 3 es Trimestre')
            ->assertSee('Nombre de la forma de trabajo')
            ->assertDontSee('Quimestres (2 ciclos)')
            ->assertDontSee('Trimestres (3 ciclos)')
            ->assertSee('Parciales por ciclo')
            ->assertSee('Primer quimestre')
            ->assertSee('Ponderación de los ciclos')
            ->assertSee('Ponderación final')
            ->assertSee('Suma de los ciclos, el examen final y el proyecto final')
            ->assertSee('Examen final')
            ->assertSee('Proyecto final')
            ->assertSee('primer ciclo 30%')
            ->assertSee('examen final 20%')
            ->assertSee('Guardar configuración')
            ->assertSee('Aperturar periodo')
            ->assertSee('Guarda la configuración antes de aperturar el periodo.')
            ->assertDontSee('Este periodo ya fue aperturado')
            ->assertDontSee(route('Admin.periodo.aperturar'), false);
    });

    it('does not show grade-specific weighting on the period configuration screen', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $gradoCuantitativo = Sys_Grado::factory()->create(['nombre' => 'Octavo EGB']);
        $asignatura = Sys_Asignatura::factory()->create(['nombre' => 'Matemática']);
        EstablecimientoAsignatura::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'grado_id' => $gradoCuantitativo->id,
            'asignatura_id' => $asignatura->id,
            'tipo_calificacion' => TipoCalificacion::Cuantitativa,
        ]);
        EstablecimientoAsignatura::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'grado_id' => $gradoCuantitativo->id,
            'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Educación Física'])->id,
            'tipo_calificacion' => TipoCalificacion::Cualitativa,
        ]);
        EstablecimientoAsignatura::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'grado_id' => Sys_Grado::factory()->create(['nombre' => 'Cuarto EGB'])->id,
            'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Lengua por destrezas'])->id,
            'tipo_calificacion' => TipoCalificacion::Cualitativa,
            'califica_por_destrezas' => true,
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->get(route('Admin.periodo'))
            ->assertOk()
            ->assertDontSee('Configuración por Grados')
            ->assertDontSee('Octavo EGB')
            ->assertDontSee('Matemática')
            ->assertDontSee('Cuarto EGB')
            ->assertDontSee('Lengua por destrezas')
            ->assertDontSee('Educación Física');
    });

    it('explains when there is no active period', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->get(route('Admin.periodo'))
            ->assertOk()
            ->assertSee('No existe ningún periodo activo.')
            ->assertDontSee('Guardar configuración');
    });

    it('explains when the establishment has no ofertas', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->get(route('Admin.periodo'))
            ->assertOk()
            ->assertSee('Sistemas aún no ha asignado modalidades y jornadas a este establecimiento.')
            ->assertDontSee('Guardar configuración');
    });

    it('redirects administrators to choose an oferta when several exist and none is selected', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        ofertaDe($establecimiento, ['jornada' => 'Matutina']);
        ofertaDe($establecimiento, ['jornada' => 'Vespertina']);

        $this->actingAs($admin)
            ->get(route('Admin.periodo'))
            ->assertRedirect(route('oferta.select'));
    });

    it('escapes the work form name on the periodo screen', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta, [
            'esquema_ciclo' => EsquemaCiclo::Otro,
            'nombre_esquema' => "<script>alert('forma')</script>",
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->get(route('Admin.periodo'))
            ->assertOk()
            ->assertSee('\u003Cscript\u003Ealert(\u0027forma\u0027)\u003C\/script\u003E', false)
            ->assertDontSee("<script>alert('forma')</script>", false);
    });

    it('escapes period names on the periodo screen', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta, ['nombre' => "<script>alert('xss')</script>"]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->get(route('Admin.periodo'))
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });

    it('forbids systems users from opening periodo configuration', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get(route('Admin.periodo'))
            ->assertForbidden();
    });

    it('redirects guests from periodo configuration to login', function () {
        $this->get(route('Admin.periodo'))
            ->assertRedirect(route('login'));
    });

    it('forbids administrators without an establishment', function () {
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.periodo'))
            ->assertForbidden();
    });

    it('shows the periodo option to administrators', function () {
        $this->seed(NavigationSeeder::class);
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertSee('Periodo')
            ->assertSee(route('Admin.periodo'), false);
    });
});

describe('update', function () {
    it('saves custom cycle weights for quantitative subjects in selected grades', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $grado = Sys_Grado::factory()->create(['nombre' => 'Primero de Bachillerato']);
        $asignatura = Sys_Asignatura::factory()->create(['nombre' => 'Matemática']);
        $malla = EstablecimientoAsignatura::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'grado_id' => $grado->id,
            'asignatura_id' => $asignatura->id,
            'tipo_calificacion' => TipoCalificacion::Cuantitativa,
        ]);
        $payload = evaluacionQuimestral();
        $payload['configuracion_por_grado'] = '1';
        $payload['grados_configurados'] = [(string) $grado->id];
        $payload['ponderaciones_grado'] = [
            $grado->id => [$malla->id => [1 => '40', 2 => '60']],
        ];

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHas('status', 'periodo-evaluacion-updated');

        expect(EstablecimientoPeriodoAsignaturaCiclo::query()
            ->where('establecimiento_periodo_id', $periodo->id)
            ->where('establecimiento_asignatura_id', $malla->id)
            ->orderBy('ciclo_orden')
            ->pluck('porcentaje')
            ->all())->toBe(['40.00', '60.00']);

        $payloadSinGrados = evaluacionQuimestral();
        $payloadSinGrados['configuracion_por_grado'] = '1';

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->put(route('Admin.periodo.update'), $payloadSinGrados)
            ->assertRedirect(route('Admin.periodo'));

        expect(EstablecimientoPeriodoAsignaturaCiclo::query()
            ->where('establecimiento_periodo_id', $periodo->id)
            ->count())->toBe(0);
    });

    it('rejects custom cycle weights that do not add up to the period total', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $grado = Sys_Grado::factory()->create(['nombre' => 'Primero de Bachillerato']);
        $malla = EstablecimientoAsignatura::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'grado_id' => $grado->id,
            'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Matemática'])->id,
            'tipo_calificacion' => TipoCalificacion::Cuantitativa,
        ]);
        $payload = evaluacionQuimestral();
        $payload['configuracion_por_grado'] = '1';
        $payload['grados_configurados'] = [(string) $grado->id];
        $payload['ponderaciones_grado'] = [
            $grado->id => [$malla->id => [1 => '40', 2 => '50']],
        ];

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.periodo'))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHasErrors([
                'ponderaciones_grado.'.$grado->id.'.'.$malla->id => 'Las ponderaciones de los ciclos de cada asignatura, junto con el examen y proyecto final, deben sumar 100%.',
            ]);

        expect(EstablecimientoPeriodoAsignaturaCiclo::query()->count())->toBe(0);
    });

    it('saves the evaluation scheme of the active period', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->put(route('Admin.periodo.update'), evaluacionQuimestral())
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHas('status', 'periodo-evaluacion-updated');

        $periodo->refresh();

        expect($periodo->esquema_ciclo)->toBe(EsquemaCiclo::Quimestres)
            ->and($periodo->nombre_esquema)->toBe('Quimestres')
            ->and($periodo->numero_parciales)->toBe(2)
            ->and($periodo->porcentaje_examen_final)->toBe('0.00')
            ->and($periodo->porcentaje_proyecto_final)->toBe('0.00')
            ->and($periodo->abierto_en)->toBeNull()
            ->and(EstablecimientoPeriodoCicloParcial::query()->count())->toBe(0);

        $ciclos = $periodo->ciclos()->orderBy('orden')->get();

        expect($ciclos)->toHaveCount(2);
        expect($ciclos[0]->nombre)->toBe('Primer quimestre');
        expect($ciclos[0]->porcentaje)->toBe('50.00');
        expect($ciclos[0]->porcentaje_insumos)->toBe('70.00');
        expect($ciclos[0]->porcentaje_examen)->toBe('30.00');
        expect($ciclos[0]->porcentaje_proyecto)->toBe('0.00');
        expect($ciclos[1]->nombre)->toBe('Segundo quimestre');
        expect($ciclos[1]->porcentaje_insumos)->toBe('80.00');
        expect($ciclos[1]->porcentaje_examen)->toBe('0.00');
        expect($ciclos[1]->porcentaje_proyecto)->toBe('20.00');
    });

    it('replaces previous cycles of the same period', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta, [
            'esquema_ciclo' => EsquemaCiclo::Trimestres,
            'numero_parciales' => 3,
        ]);
        EstablecimientoPeriodoCiclo::factory()->create([
            'establecimiento_periodo_id' => $periodo->id,
            'orden' => 1,
            'nombre' => 'Viejo',
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->put(route('Admin.periodo.update'), evaluacionQuimestral())
            ->assertRedirect(route('Admin.periodo'));

        expect($periodo->ciclos()->count())->toBe(2)
            ->and($periodo->fresh()->esquema_ciclo)->toBe(EsquemaCiclo::Quimestres)
            ->and($periodo->ciclos()->where('nombre', 'Viejo')->exists())->toBeFalse();
    });

    it('does not change the period of another oferta', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $otra = ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
        $periodoAjeno = periodoActivoDe($otra, ['nombre' => 'Ajeno']);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->put(route('Admin.periodo.update'), evaluacionQuimestral())
            ->assertRedirect(route('Admin.periodo'));

        expect($periodoAjeno->fresh()->esquema_ciclo)->toBeNull()
            ->and($periodoAjeno->fresh()->nombre_esquema)->toBeNull()
            ->and($periodoAjeno->ciclos()->count())->toBe(0);
    });

    it('rejects an empty evaluation payload', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.periodo'))
            ->put(route('Admin.periodo.update'), [])
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHasErrors([
                'esquema_ciclo',
                'nombre_esquema',
                'numero_parciales',
                'ciclos',
            ]);
    });

    it('rejects cycle weights that do not add up to 100 percent', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $payload = evaluacionQuimestral();
        $payload['ciclos'][0]['porcentaje'] = '10';
        $payload['ciclos'][1]['porcentaje'] = '20';

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.periodo'))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHasErrors([
                'totales' => 'Los ciclos, el examen final y el proyecto final deben sumar 100%.',
            ]);
    });

    it('saves a period-level final exam and project with the cycles', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $payload = evaluacionQuimestral();
        $payload['ciclos'][0]['porcentaje'] = '30';
        $payload['ciclos'][1]['porcentaje'] = '30';
        $payload['tiene_examen_final'] = '1';
        $payload['porcentaje_examen_final'] = '20';
        $payload['tiene_proyecto_final'] = '1';
        $payload['porcentaje_proyecto_final'] = '20';

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'));

        $periodo->refresh();

        expect($periodo->porcentaje_examen_final)->toBe('20.00')
            ->and($periodo->porcentaje_proyecto_final)->toBe('20.00');

        $ciclos = $periodo->ciclos()->orderBy('orden')->get();

        expect($ciclos[0]->porcentaje)->toBe('30.00')
            ->and($ciclos[1]->porcentaje)->toBe('30.00');
    });

    it('saves the work form name the user gives', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $payload = evaluacionQuimestral();
        $payload['nombre_esquema'] = 'Quimestres Costa';

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'));

        expect($periodo->fresh()->nombre_esquema)->toBe('Quimestres Costa')
            ->and($periodo->fresh()->esquema_ciclo)->toBe(EsquemaCiclo::Quimestres)
            ->and($periodo->ciclos()->count())->toBe(2);
    });

    it('saves a custom work form with the cycle count the user gives', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $payload = evaluacionQuimestral();
        $payload['esquema_ciclo'] = EsquemaCiclo::Otro->value;
        $payload['nombre_esquema'] = 'Bimestres';
        $ciclo = [
            'porcentaje' => '25',
            'porcentaje_insumos' => '70',
            'tiene_examen' => '1',
            'porcentaje_examen' => '30',
            'tiene_proyecto' => '0',
            'porcentaje_proyecto' => '0',
        ];
        $payload['ciclos'] = [
            ['nombre' => 'Primer bimestre', ...$ciclo],
            ['nombre' => 'Segundo bimestre', ...$ciclo],
            ['nombre' => 'Tercer bimestre', ...$ciclo],
            ['nombre' => 'Cuarto bimestre', ...$ciclo],
        ];

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'));

        $periodo->refresh();

        expect($periodo->esquema_ciclo)->toBe(EsquemaCiclo::Otro)
            ->and($periodo->nombre_esquema)->toBe('Bimestres')
            ->and($periodo->ciclos()->orderBy('orden')->pluck('nombre')->all())->toBe([
                'Primer bimestre',
                'Segundo bimestre',
                'Tercer bimestre',
                'Cuarto bimestre',
            ]);
    });

    it('rejects a work form without a name', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $payload = evaluacionQuimestral();
        $payload['nombre_esquema'] = '   ';

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.periodo'))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHasErrors([
                'nombre_esquema' => 'Indica el nombre de la forma de trabajo.',
            ]);
    });

    it('rejects a work form name longer than 255 characters', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $payload = evaluacionQuimestral();
        $payload['nombre_esquema'] = str_repeat('a', 256);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.periodo'))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHasErrors([
                'nombre_esquema' => 'El nombre de la forma de trabajo no puede superar 255 caracteres.',
            ]);
    });

    it('rejects a quimestral scheme that does not have two cycles', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $payload = evaluacionQuimestral();
        unset($payload['ciclos'][1]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.periodo'))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHasErrors([
                'ciclos' => 'Los quimestres deben tener 2 ciclos.',
            ]);
    });

    it('rejects insumos, exam and project weights that do not add up to 100 percent of the cycle', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $payload = evaluacionQuimestral();
        $payload['ciclos'][0]['porcentaje_insumos'] = '10';
        $payload['ciclos'][0]['porcentaje_examen'] = '10';
        $payload['ciclos'][0]['porcentaje_proyecto'] = '10';

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.periodo'))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHasErrors([
                'ciclos.0.porcentaje_insumos' => 'Los insumos, el examen y el proyecto del ciclo deben sumar 100%.',
            ]);
    });

    it('saves a cycle that has both an exam and a project', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $payload = evaluacionQuimestral();
        $payload['ciclos'][0]['tiene_examen'] = '1';
        $payload['ciclos'][0]['porcentaje_examen'] = '20';
        $payload['ciclos'][0]['tiene_proyecto'] = '1';
        $payload['ciclos'][0]['porcentaje_proyecto'] = '10';
        $payload['ciclos'][0]['porcentaje_insumos'] = '70';

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'));

        $ciclo = $periodo->ciclos()->orderBy('orden')->first();

        expect($ciclo->porcentaje_insumos)->toBe('70.00')
            ->and($ciclo->porcentaje_examen)->toBe('20.00')
            ->and($ciclo->porcentaje_proyecto)->toBe('10.00');
    });

    it('forbids updating evaluation when there is no active period', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->put(route('Admin.periodo.update'), evaluacionQuimestral())
            ->assertForbidden();
    });
});

/**
 * @return array{
 *     esquema_ciclo: string,
 *     nombre_esquema: string,
 *     numero_parciales: int,
 *     tiene_examen_final: string,
 *     porcentaje_examen_final: string,
 *     tiene_proyecto_final: string,
 *     porcentaje_proyecto_final: string,
 *     ciclos: list<array{nombre: string, porcentaje: string, porcentaje_insumos: string, tiene_examen: string, porcentaje_examen: string, tiene_proyecto: string, porcentaje_proyecto: string}>
 * }
 */
describe('aperturar', function () {
    it('shows the open action when the evaluation is already saved', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoConEvaluacion($oferta, 2);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->get(route('Admin.periodo'))
            ->assertOk()
            ->assertSee('Al aperturar, esta configuración se bloquea')
            ->assertSee(route('Admin.periodo.aperturar'), false)
            ->assertSee('Guardar configuración')
            ->assertDontSee('Este periodo ya fue aperturado')
            ->assertDontSee('Guarda la configuración antes de aperturar el periodo.');
    });

    it('locks the configuration for consultation after the period is open', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoConEvaluacion($oferta, 2);
        $periodo->update(['abierto_en' => now()]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->get(route('Admin.periodo'))
            ->assertOk()
            ->assertSee('Este periodo ya fue aperturado')
            ->assertSee('La configuración quedó bloqueada. Puedes consultarla, pero no modificar la forma de trabajo, los ciclos ni la ponderación final.')
            ->assertSee('Primer quimestre')
            ->assertSee('Ponderación de los ciclos')
            ->assertSee('Ponderación final')
            ->assertSee('<fieldset class="min-w-0 w-full border-0 p-0" disabled>', false)
            ->assertDontSee('Guardar configuración')
            ->assertDontSee('Aperturar periodo')
            ->assertDontSee(route('Admin.periodo.aperturar'), false);
    });

    it('creates one pending close for every parcial of every cycle', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoConEvaluacion($oferta, 3);
        $momento = now()->startOfSecond();
        $this->travelTo($momento);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->post(route('Admin.periodo.aperturar'))
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHas('status', 'periodo-aperturado');

        $periodo->refresh()->load('ciclos.parciales');

        expect($periodo->estaAbierto())->toBeTrue()
            ->and($periodo->abierto_en?->toDateTimeString())->toBe($momento->toDateTimeString())
            ->and($periodo->ciclos)->toHaveCount(2);

        foreach ($periodo->ciclos as $ciclo) {
            expect($ciclo->parciales)->toHaveCount(3)
                ->and($ciclo->parciales->pluck('numero')->all())->toBe([1, 2, 3])
                ->and($ciclo->parciales->every(fn (EstablecimientoPeriodoCicloParcial $parcial): bool => $parcial->cerrado_en === null))->toBeTrue();
        }
    });

    it('does not open the period of another oferta', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoConEvaluacion($oferta, 2);
        $otra = ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
        $periodoAjeno = periodoConEvaluacion($otra, 2);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->post(route('Admin.periodo.aperturar'))
            ->assertRedirect(route('Admin.periodo'));

        expect($periodo->fresh()->estaAbierto())->toBeTrue()
            ->and($periodoAjeno->fresh()->estaAbierto())->toBeFalse()
            ->and($periodoAjeno->ciclos()->withCount('parciales')->get()->sum('parciales_count'))->toBe(0);
    });

    it('rejects a second opening without duplicating parcial closes', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoConEvaluacion($oferta, 2);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->post(route('Admin.periodo.aperturar'))
            ->assertRedirect(route('Admin.periodo'));

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.periodo'))
            ->post(route('Admin.periodo.aperturar'))
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHasErrors([
                'periodo' => 'Este periodo ya fue aperturado. La configuración está bloqueada.',
            ]);

        expect($periodo->ciclos()->withCount('parciales')->get()->sum('parciales_count'))->toBe(4);
    });

    it('rejects opening before the evaluation is saved', function (bool $conCiclos) {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta, [
            'esquema_ciclo' => EsquemaCiclo::Quimestres,
            'nombre_esquema' => 'Quimestre',
            'numero_parciales' => null,
        ]);

        if ($conCiclos) {
            EstablecimientoPeriodoCiclo::factory()->create([
                'establecimiento_periodo_id' => $periodo->id,
                'orden' => 1,
                'nombre' => 'Primer quimestre',
            ]);
        }

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.periodo'))
            ->post(route('Admin.periodo.aperturar'))
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHasErrors([
                'periodo' => 'Guarda la configuración del periodo antes de aperturarlo.',
            ]);

        expect($periodo->fresh()->estaAbierto())->toBeFalse()
            ->and(EstablecimientoPeriodoCicloParcial::query()->count())->toBe(0);
    })->with([
        'sin ciclos' => false,
        'sin parciales' => true,
    ]);

    it('rejects configuration changes after the period is open', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoConEvaluacion($oferta, 2);
        $periodo->update(['abierto_en' => now()]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.periodo'))
            ->put(route('Admin.periodo.update'), evaluacionQuimestral())
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHasErrors([
                'periodo' => 'Este periodo ya fue aperturado. La configuración está bloqueada y solo se puede consultar.',
            ]);

        expect($periodo->fresh()->nombre_esquema)->toBe('Quimestre')
            ->and($periodo->ciclos()->where('nombre', 'Primer quimestre')->exists())->toBeTrue()
            ->and($periodo->ciclos()->count())->toBe(2)
            ->and($periodo->ciclos()->withCount('parciales')->get()->sum('parciales_count'))->toBe(0);
    });

    it('does not open the period from the configuration form', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $payload = evaluacionQuimestral();
        $payload['abierto_en'] = now()->toDateTimeString();

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'));

        expect($periodo->fresh()->estaAbierto())->toBeFalse()
            ->and(EstablecimientoPeriodoCicloParcial::query()->count())->toBe(0);
    });

    it('forbids systems users from opening a period', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->post(route('Admin.periodo.aperturar'))
            ->assertForbidden();
    });

    it('redirects guests from opening a period to login', function () {
        $this->post(route('Admin.periodo.aperturar'))
            ->assertRedirect(route('login'));
    });
});

function evaluacionQuimestral(): array
{
    return [
        'esquema_ciclo' => EsquemaCiclo::Quimestres->value,
        'nombre_esquema' => 'Quimestres',
        'numero_parciales' => 2,
        'tiene_examen_final' => '0',
        'porcentaje_examen_final' => '0',
        'tiene_proyecto_final' => '0',
        'porcentaje_proyecto_final' => '0',
        'ciclos' => [
            [
                'nombre' => 'Primer quimestre',
                'porcentaje' => '50',
                'porcentaje_insumos' => '70',
                'tiene_examen' => '1',
                'porcentaje_examen' => '30',
                'tiene_proyecto' => '0',
                'porcentaje_proyecto' => '0',
            ],
            [
                'nombre' => 'Segundo quimestre',
                'porcentaje' => '50',
                'porcentaje_insumos' => '80',
                'tiene_examen' => '0',
                'porcentaje_examen' => '0',
                'tiene_proyecto' => '1',
                'porcentaje_proyecto' => '20',
            ],
        ],
    ];
}

function periodoConEvaluacion(EstablecimientoModalidadJornada $oferta, int $parciales): EstablecimientoPeriodo
{
    $periodo = periodoActivoDe($oferta, [
        'esquema_ciclo' => EsquemaCiclo::Quimestres,
        'nombre_esquema' => 'Quimestre',
        'numero_parciales' => $parciales,
        'porcentaje_examen_final' => '0.00',
        'porcentaje_proyecto_final' => '0.00',
    ]);

    EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'orden' => 1,
        'nombre' => 'Primer quimestre',
        'porcentaje' => '50.00',
    ]);

    EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'orden' => 2,
        'nombre' => 'Segundo quimestre',
        'porcentaje' => '50.00',
    ]);

    return $periodo;
}
