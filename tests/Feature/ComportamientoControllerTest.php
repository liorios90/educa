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
use App\Models\Sys_CriterioComportamiento;
use App\Models\User;
use App\Services\EscalaComportamiento;
use Database\Seeders\NavigationSeeder;

/**
 * @return array{
 *     establecimiento: Establecimiento,
 *     docente: User,
 *     aula: EstablecimientoAula,
 *     asignatura: EstablecimientoAsignatura,
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

    return [
        'establecimiento' => $establecimiento,
        'docente' => $docente,
        'aula' => $aula,
        'asignatura' => $malla,
        'ciclo' => $ciclo,
    ];
}

/**
 * @param  array{aula: EstablecimientoAula}  $escenario
 * @return array{0: Sys_CriterioComportamiento, 1: Sys_CriterioComportamiento}
 */
function indicadoresDeComportamiento(array $escenario): array
{
    $periodo = EstablecimientoPeriodo::query()->findOrFail($escenario['aula']->establecimiento_periodo_id);
    $primero = Sys_CriterioComportamiento::factory()->create([
        'codigo' => 'IND-'.fake()->unique()->numerify('####'),
        'descripcion' => 'Autoconocimiento',
    ]);
    $segundo = Sys_CriterioComportamiento::factory()->create([
        'codigo' => 'IND-'.fake()->unique()->numerify('####'),
        'descripcion' => 'Manejo de conflictos',
    ]);
    $periodo->criteriosComportamiento()->sync([$primero->id, $segundo->id]);

    return [$primero, $segundo];
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
            'establecimiento_asignatura_id' => $escenario['asignatura']->id,
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
        'establecimiento_asignatura_id' => $escenario['asignatura']->id,
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
        indicadoresDeComportamiento($escenario);
        abrirComportamiento($escenario);

        $this->get(route('Docente.comportamiento.index'))
            ->assertSee('Comportamiento')
            ->assertSee('Andrade Luis')
            ->assertSee('Autoconocimiento')
            ->assertSee('Manejo de conflictos')
            ->assertSee('Siempre')
            ->assertSee('Nunca')
            ->assertSee('no modifica el promedio');
    });

    it('asks the administrator to configure the scale when the period has none', function () {
        $escenario = escenarioComportamiento();
        alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        abrirComportamiento($escenario);

        $this->get(route('Docente.comportamiento.index'))
            ->assertSee('El periodo no tiene una tabla de comportamiento activa.')
            ->assertDontSee('Siempre');
    });

    it('asks the administrator to choose the behavior indicators', function () {
        $escenario = escenarioComportamiento();
        alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        escalaReglamento($escenario);
        abrirComportamiento($escenario);

        $this->get(route('Docente.comportamiento.index'))
            ->assertSee('El periodo no tiene indicadores de comportamiento.')
            ->assertDontSee('Autoconocimiento');
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
        escalaReglamento($escenario);
        indicadoresDeComportamiento($escenario);
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
                'establecimiento_asignatura_id' => 'Selecciona una asignatura.',
                'establecimiento_periodo_ciclo_id' => 'Selecciona un quimestre o trimestre.',
                'comportamientos' => 'Registra el comportamiento de cada estudiante.',
            ]);
    });

    it('saves one grade per indicator and replaces it', function () {
        $escenario = escenarioComportamiento();
        $alumno = alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        $escala = escalaReglamento($escenario);
        [$primero, $segundo] = indicadoresDeComportamiento($escenario);
        $muySatisfactorio = calificacionId($escala, 'S');
        $satisfactorio = calificacionId($escala, 'F');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
                $alumno->id => [
                    $primero->id => ['calificacion' => $muySatisfactorio, 'observacion' => 'Lidera la convivencia.'],
                    $segundo->id => ['calificacion' => $satisfactorio],
                ],
            ]))
            ->assertRedirect(route('Docente.comportamiento.index'));

        $guardado = EstablecimientoComportamiento::query()->where('criterio_comportamiento_id', $primero->id)->first();

        expect(EstablecimientoComportamiento::query()->count())->toBe(2)
            ->and($guardado)->not->toBeNull()
            ->and($guardado->alumno_id)->toBe($alumno->id)
            ->and($guardado->establecimiento_aula_id)->toBe($escenario['aula']->id)
            ->and($guardado->establecimiento_asignatura_id)->toBe($escenario['asignatura']->id)
            ->and($guardado->establecimiento_periodo_ciclo_id)->toBe($escenario['ciclo']->id)
            ->and($guardado->equivalencia_detalle_id)->toBe($muySatisfactorio)
            ->and($guardado->observacion)->toBe('Lidera la convivencia.');

        $this->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
            $alumno->id => [
                $primero->id => ['calificacion' => $satisfactorio],
                $segundo->id => ['calificacion' => $satisfactorio],
            ],
        ]))->assertRedirect();

        expect(EstablecimientoComportamiento::query()->count())->toBe(2)
            ->and(EstablecimientoComportamiento::query()->where('criterio_comportamiento_id', $primero->id)->first()?->equivalencia_detalle_id)->toBe($satisfactorio)
            ->and(EstablecimientoComportamiento::query()->where('criterio_comportamiento_id', $primero->id)->first()?->observacion)->toBeNull();
    });

    it('requires the convivencia note when the grade shows a repeated or total failure', function (string $valor) {
        $escenario = escenarioComportamiento();
        $alumno = alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        $escala = escalaReglamento($escenario);
        [$primero, $segundo] = indicadoresDeComportamiento($escenario);
        $calificacion = calificacionId($escala, $valor);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
                $alumno->id => [
                    $primero->id => ['calificacion' => $calificacion],
                    $segundo->id => ['calificacion' => calificacionId($escala, 'S')],
                ],
            ]))
            ->assertSessionHasErrors([
                'comportamientos.'.$alumno->id.'.'.$primero->id.'.observacion' => 'Describe en qué situaciones no se observa la habilidad.',
            ]);

        $this->assertDatabaseCount('establecimiento_comportamientos', 0);
    })->with([
        'ocasionalmente' => 'O',
        'nunca' => 'N',
    ]);

    it('stores the convivencia note for an unsatisfactory grade', function () {
        $escenario = escenarioComportamiento();
        $alumno = alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        $escala = escalaReglamento($escenario);
        [$primero, $segundo] = indicadoresDeComportamiento($escenario);
        $insatisfactorio = calificacionId($escala, 'N');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
                $alumno->id => [
                    $primero->id => [
                        'calificacion' => $insatisfactorio,
                        'observacion' => 'No cumple los acuerdos de respeto en el aula.',
                    ],
                    $segundo->id => ['calificacion' => calificacionId($escala, 'S')],
                ],
            ]))
            ->assertRedirect();

        $guardado = EstablecimientoComportamiento::query()->where('criterio_comportamiento_id', $primero->id)->first();

        expect($guardado?->equivalencia_detalle_id)->toBe($insatisfactorio)
            ->and($guardado?->observacion)->toBe('No cumple los acuerdos de respeto en el aula.');
    });

    it('records a custom scale configured for the period', function () {
        $escenario = escenarioComportamiento();
        $alumno = alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        [$primero, $segundo] = indicadoresDeComportamiento($escenario);
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
            'descripcion' => 'Cumple los indicadores de forma destacada.',
            'valor' => 'MB',
            'jerarquia' => 2,
            'activo' => true,
            'exige_observacion' => false,
        ]);
        $noCumple = EquivalenciaDetalle::query()->create([
            'equivalencia_id' => $equivalencia->id,
            'nombre' => 'No cumple',
            'descripcion' => 'No cumple los indicadores.',
            'valor' => 'NC',
            'jerarquia' => 1,
            'activo' => true,
            'exige_observacion' => true,
        ]);
        abrirComportamiento($escenario);

        $this->get(route('Docente.comportamiento.index'))
            ->assertSee('MB — Muy bueno')
            ->assertSee('NC — No cumple')
            ->assertDontSee('Siempre');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
                $alumno->id => [
                    $primero->id => ['calificacion' => $muyBueno->id],
                    $segundo->id => ['calificacion' => $muyBueno->id],
                ],
            ]))
            ->assertRedirect();

        expect(EstablecimientoComportamiento::query()->count())->toBe(2);

        $this->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
            $alumno->id => [
                $primero->id => ['calificacion' => $noCumple->id],
                $segundo->id => ['calificacion' => $muyBueno->id],
            ],
        ]))->assertSessionHasErrors([
            'comportamientos.'.$alumno->id.'.'.$primero->id.'.observacion' => 'Describe en qué situaciones no se observa la habilidad.',
        ]);
    });

    it('rejects a missing behavior indicator', function () {
        $escenario = escenarioComportamiento();
        $alumno = alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        $escala = escalaReglamento($escenario);
        [$primero] = indicadoresDeComportamiento($escenario);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
                $alumno->id => [
                    $primero->id => ['calificacion' => calificacionId($escala, 'S')],
                ],
            ]))
            ->assertSessionHasErrors([
                'comportamientos' => 'La lista de indicadores no coincide con el periodo. Vuelve a cargar el comportamiento.',
            ]);

        $this->assertDatabaseCount('establecimiento_comportamientos', 0);
    });

    it('rejects a student who is not on the class roster', function () {
        $escenario = escenarioComportamiento();
        alumnoDeComportamiento($escenario, 'Luis', 'Andrade');
        $ajeno = Alumno::factory()->create();
        $escala = escalaReglamento($escenario);
        [$primero, $segundo] = indicadoresDeComportamiento($escenario);
        $calificacion = calificacionId($escala, 'S');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
                $ajeno->id => [
                    $primero->id => ['calificacion' => $calificacion],
                    $segundo->id => ['calificacion' => $calificacion],
                ],
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
        $escala = escalaReglamento($escenario);
        [$primero, $segundo] = indicadoresDeComportamiento($escenario);
        $calificacion = calificacionId($escala, 'S');

        $this->actingAs($ajeno['docente'])
            ->post(route('Docente.comportamiento.registrar'), registroComportamiento($escenario, [
                $alumno->id => [
                    $primero->id => ['calificacion' => $calificacion],
                    $segundo->id => ['calificacion' => $calificacion],
                ],
            ]))
            ->assertNotFound();
    });
});
