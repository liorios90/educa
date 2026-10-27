<?php

use App\Auth\ActiveRole;
use App\Enums\EsquemaCiclo;
use App\Enums\Role;
use App\Enums\TipoAsignatura;
use App\Enums\TipoCalificacion;
use App\Enums\UsoEquivalencia;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\Equivalencia;
use App\Models\EquivalenciaDetalle;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoComportamiento;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\NavigationItem;
use App\Models\Persona;
use App\Models\Sys_Asignatura;
use App\Models\User;
use App\Services\EscalaComportamiento;
use Database\Seeders\NavigationSeeder;

/**
 * @return array{
 *     establecimiento: Establecimiento,
 *     docente: User,
 *     aula: EstablecimientoAula,
 *     ciclo: EstablecimientoPeriodoCiclo
 * }
 */
function escenarioComportamiento(): array
{
    $establecimiento = Establecimiento::factory()->create();
    $docente = assignRole(User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'name' => 'Ana Perez',
    ]), Role::Docente);
    $persona = Persona::factory()->create([
        'user_id' => $docente->id,
        'establecimiento_id' => $establecimiento->id,
        'nombres' => 'Ana',
        'apellidos' => 'Perez',
    ]);
    $empleado = Empleado::factory()->for($persona)->create();
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta, [
        'esquema_ciclo' => EsquemaCiclo::Quimestres,
        'numero_parciales' => 2,
        'abierto_en' => now(),
    ]);
    ['grado' => $catalogo] = catalogoEstructura([
        'nivel' => 'Nivel '.fake()->unique()->numerify('####'),
        'subnivel' => 'Subnivel '.fake()->unique()->numerify('####'),
        'grado' => 'Grado '.fake()->unique()->numerify('####'),
    ]);
    $grado = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'subnivel_id' => $catalogo->subnivel_id,
        'grado_id' => $catalogo->id,
    ]);
    $aula = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $grado->id,
        'paralelo' => 'A',
    ]);
    $malla = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $catalogo->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Matemática'])->id,
        'tipo_calificacion' => TipoCalificacion::Cuantitativa,
        'tipo_asignatura' => TipoAsignatura::TroncoComun,
        'es_flotante' => false,
    ]);
    EstablecimientoAulaDocente::factory()->create([
        'establecimiento_aula_id' => $aula->id,
        'establecimiento_asignatura_id' => $malla->id,
        'empleado_id' => $empleado->id,
    ]);
    $ciclo = EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'orden' => 1,
        'nombre' => 'Primer quimestre',
    ]);

    return compact('establecimiento', 'docente', 'aula', 'ciclo');
}

function alumnoDeComportamiento(array $escenario, string $nombres, string $apellidos): Alumno
{
    $persona = Persona::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'nombres' => $nombres,
        'apellidos' => $apellidos,
    ]);
    $alumno = Alumno::factory()->create(['persona_id' => $persona->id]);
    EstablecimientoMatricula::factory()->create([
        'establecimiento_id' => $escenario['aula']->establecimiento_id,
        'establecimiento_periodo_id' => $escenario['aula']->establecimiento_periodo_id,
        'establecimiento_aula_id' => $escenario['aula']->id,
        'alumno_id' => $alumno->id,
    ]);

    return $alumno;
}

function escalaReglamento(array $escenario): Equivalencia
{
    $periodo = EstablecimientoPeriodo::query()->findOrFail($escenario['aula']->establecimiento_periodo_id);

    return app(EscalaComportamiento::class)->crearReglamento($periodo);
}

function calificacionId(Equivalencia $escala, string $valor): int
{
    $detalle = $escala->detalles->firstWhere('valor', $valor);

    expect($detalle)->not->toBeNull();

    return (int) $detalle->id;
}

function abrirComportamiento(array $escenario): void
{
    test()->actingAs($escenario['docente'])
        ->post(route('Docente.comportamiento.store'), [
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        ])
        ->assertRedirect(route('Docente.comportamiento.index'));
}

/**
 * @param  array<int, array{calificacion: string, observacion?: string|null}>  $filas
 * @return array<string, mixed>
 */
function registroComportamiento(array $escenario, array $filas): array
{
    return [
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'comportamientos' => $filas,
    ];
}

describe('index', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('Docente.comportamiento.index'))
            ->assertRedirect(route('login'));
    });

    it('forbids an administrator from opening the behavior register', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('Docente.comportamiento.index'))
            ->assertForbidden();
    });

    it('renders the qualitative scale for the classroom', function () {
        $escenario = escenarioComportamiento();
        alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        escalaReglamento($escenario);
        abrirComportamiento($escenario);

        $this->get(route('Docente.comportamiento.index'))
            ->assertSee('Comportamiento')
            ->assertSee('Andrade Luis')
            ->assertSee('Muy satisfactorio')
            ->assertSee('Insatisfactorio')
            ->assertSee('no modifica el promedio');
    });

    it('asks the administrator to configure the scale when the period has none', function () {
        $escenario = escenarioComportamiento();
        alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        abrirComportamiento($escenario);

        $this->get(route('Docente.comportamiento.index'))
            ->assertSee('El periodo no tiene una tabla de comportamiento activa.')
            ->assertDontSee('Muy satisfactorio');
    });

    it('shows behavior inside the insumos menu', function () {
        $this->seed(NavigationSeeder::class);
        $escenario = escenarioComportamiento();
        $insumos = NavigationItem::query()->where('label', 'Insumos')->where('is_group', true)->firstOrFail();

        $this->actingAs($escenario['docente'])
            ->withSession([ActiveRole::SESSION_KEY => Role::Docente->value])
            ->get(route('navigation.hub', $insumos))
            ->assertSee('Comportamiento')
            ->assertSee(route('Docente.comportamiento.index'), false)
            ->assertSee('No modifica el promedio');
    });

    it('escapes a dangerous student name', function () {
        $escenario = escenarioComportamiento();
        alumnoDeComportamiento($escenario, 'Luis', "<script>alert('xss')</script>");
        abrirComportamiento($escenario);

        $this->get(route('Docente.comportamiento.index'))
            ->assertDontSee("<script>alert('xss')</script>", false);
    });
});

describe('registrar', function () {
    it('forbids an administrator from recording behavior', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->post(route('Docente.comportamiento.registrar'), [])
            ->assertForbidden();
    });

    it('requires the classroom, the cycle and every student', function () {
        $escenario = escenarioComportamiento();

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.comportamiento.registrar'), [])
            ->assertSessionHasErrors([
                'establecimiento_aula_id' => 'Selecciona un aula.',
                'establecimiento_periodo_ciclo_id' => 'Selecciona un quimestre o trimestre.',
                'comportamientos' => 'Registra el comportamiento de cada estudiante.',
            ]);
    });

    it('saves the quimestre grade and replaces it without touching subject averages', function () {
        $escenario = escenarioComportamiento();
        $alumno = alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        $escala = escalaReglamento($escenario);
        $muySatisfactorio = calificacionId($escala, 'A');
        $satisfactorio = calificacionId($escala, 'B');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
                $alumno->id => ['calificacion' => $muySatisfactorio, 'observacion' => 'Lidera la convivencia.'],
            ]))
            ->assertRedirect(route('Docente.comportamiento.index'));

        $guardado = EstablecimientoComportamiento::query()->first();

        expect($guardado)->not->toBeNull()
            ->and($guardado->alumno_id)->toBe($alumno->id)
            ->and($guardado->establecimiento_aula_id)->toBe($escenario['aula']->id)
            ->and($guardado->establecimiento_periodo_ciclo_id)->toBe($escenario['ciclo']->id)
            ->and($guardado->equivalencia_detalle_id)->toBe($muySatisfactorio)
            ->and($guardado->observacion)->toBe('Lidera la convivencia.');

        $this->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
            $alumno->id => ['calificacion' => $satisfactorio],
        ]))->assertRedirect();

        expect(EstablecimientoComportamiento::query()->count())->toBe(1)
            ->and(EstablecimientoComportamiento::query()->first()?->equivalencia_detalle_id)->toBe($satisfactorio)
            ->and(EstablecimientoComportamiento::query()->first()?->observacion)->toBeNull();
    });

    it('requires the convivencia note when the grade shows a repeated or total failure', function (string $valor) {
        $escenario = escenarioComportamiento();
        $alumno = alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        $calificacion = calificacionId(escalaReglamento($escenario), $valor);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
                $alumno->id => ['calificacion' => $calificacion],
            ]))
            ->assertSessionHasErrors([
                'comportamientos.'.$alumno->id.'.observacion' => 'Describe el incumplimiento de los compromisos de convivencia.',
            ]);

        $this->assertDatabaseCount('establecimiento_comportamientos', 0);
    })->with([
        'poco satisfactorio' => 'C',
        'mejorable' => 'D',
        'insatisfactorio' => 'E',
    ]);

    it('stores the convivencia note for an unsatisfactory grade', function () {
        $escenario = escenarioComportamiento();
        $alumno = alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        $insatisfactorio = calificacionId(escalaReglamento($escenario), 'E');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
                $alumno->id => [
                    'calificacion' => $insatisfactorio,
                    'observacion' => 'No cumple los acuerdos de respeto en el aula.',
                ],
            ]))
            ->assertRedirect();

        expect(EstablecimientoComportamiento::query()->first()?->equivalencia_detalle_id)->toBe($insatisfactorio)
            ->and(EstablecimientoComportamiento::query()->first()?->observacion)->toBe('No cumple los acuerdos de respeto en el aula.');
    });

    it('records a custom scale configured for the period', function () {
        $escenario = escenarioComportamiento();
        $alumno = alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        $periodo = EstablecimientoPeriodo::query()->findOrFail($escenario['aula']->establecimiento_periodo_id);
        $equivalencia = Equivalencia::query()->create([
            'establecimiento_periodo_id' => $periodo->id,
            'nombre' => 'Escala institucional',
            'uso' => UsoEquivalencia::Comportamiento,
            'activo' => true,
        ]);
        $muyBueno = EquivalenciaDetalle::query()->create([
            'equivalencia_id' => $equivalencia->id,
            'nombre' => 'Muy bueno',
            'valor' => 'MB',
            'activo' => true,
            'exige_observacion' => false,
        ]);
        $noCumple = EquivalenciaDetalle::query()->create([
            'equivalencia_id' => $equivalencia->id,
            'nombre' => 'No cumple',
            'valor' => 'NC',
            'activo' => true,
            'exige_observacion' => true,
        ]);
        abrirComportamiento($escenario);

        $this->get(route('Docente.comportamiento.index'))
            ->assertSee('MB — Muy bueno')
            ->assertSee('NC — No cumple')
            ->assertDontSee('Muy satisfactorio');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
                $alumno->id => ['calificacion' => $muyBueno->id],
            ]))
            ->assertRedirect();

        expect(EstablecimientoComportamiento::query()->first()?->equivalencia_detalle_id)->toBe($muyBueno->id);

        $this->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
            $alumno->id => ['calificacion' => $noCumple->id],
        ]))->assertSessionHasErrors([
            'comportamientos.'.$alumno->id.'.observacion' => 'Describe el incumplimiento de los compromisos de convivencia.',
        ]);
    });

    it('rejects a student who is not on the class roster', function () {
        $escenario = escenarioComportamiento();
        alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        $ajeno = Alumno::factory()->create();
        $calificacion = calificacionId(escalaReglamento($escenario), 'A');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
                $ajeno->id => ['calificacion' => $calificacion],
            ]))
            ->assertSessionHasErrors([
                'comportamientos' => 'La lista de estudiantes no coincide con el aula. Vuelve a cargar el comportamiento.',
            ]);

        $this->assertDatabaseCount('establecimiento_comportamientos', 0);
    });

    it('returns 404 when the classroom belongs to another teacher', function () {
        $escenario = escenarioComportamiento();
        $ajeno = escenarioComportamiento();
        $alumno = alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        $calificacion = calificacionId(escalaReglamento($escenario), 'A');

        $this->actingAs($ajeno['docente'])
            ->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
                $alumno->id => ['calificacion' => $calificacion],
            ]))
            ->assertNotFound();
    });
});
