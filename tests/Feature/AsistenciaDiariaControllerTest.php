<?php

use App\Auth\ActiveRole;
use App\Enums\EsquemaCiclo;
use App\Enums\EstadoAsistencia;
use App\Enums\ModoAsistencia;
use App\Enums\Role;
use App\Enums\TipoAsignatura;
use App\Enums\TipoCalificacion;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAsistencia;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\HorarioClase;
use App\Models\NavigationItem;
use App\Models\Persona;
use App\Models\Sys_Asignatura;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

/**
 * @return array{
 *     establecimiento: Establecimiento,
 *     docente: User,
 *     aula: EstablecimientoAula,
 *     malla: EstablecimientoAsignatura,
 *     ciclo: EstablecimientoPeriodoCiclo
 * }
 */
function escenarioAsistencia(array $atributos = []): array
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
        'esquema_ciclo' => $atributos['esquema_ciclo'] ?? EsquemaCiclo::Quimestres,
        'numero_parciales' => $atributos['numero_parciales'] ?? 2,
        'abierto_en' => now(),
    ]);
    ['grado' => $catalogo] = catalogoEstructura([
        'nivel' => 'Nivel '.fake()->unique()->numerify('####'),
        'subnivel' => $atributos['subnivel'] ?? ('Subnivel '.fake()->unique()->numerify('####')),
        'grado' => $atributos['grado'] ?? '8vo Básica',
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
        'paralelo' => $atributos['paralelo'] ?? 'A',
    ]);
    $catalogoAsignatura = Sys_Asignatura::factory()->create([
        'nombre' => $atributos['asignatura'] ?? 'Matemática',
    ]);
    $malla = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $catalogo->id,
        'asignatura_id' => $catalogoAsignatura->id,
        'tipo_calificacion' => $atributos['tipo_calificacion'] ?? TipoCalificacion::Cuantitativa,
        'tipo_asignatura' => $atributos['tipo_asignatura'] ?? TipoAsignatura::TroncoComun,
        'es_flotante' => $atributos['es_flotante'] ?? false,
    ]);
    EstablecimientoAulaDocente::factory()->create([
        'establecimiento_aula_id' => $aula->id,
        'establecimiento_asignatura_id' => $malla->id,
        'empleado_id' => $empleado->id,
    ]);
    $ciclo = EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'orden' => 1,
        'nombre' => $atributos['ciclo'] ?? 'Primer quimestre',
    ]);

    return compact('establecimiento', 'docente', 'aula', 'malla', 'ciclo', 'periodo');
}

function matricularAlumnoAsistencia(EstablecimientoAula $aula, Alumno $alumno): EstablecimientoMatricula
{
    return EstablecimientoMatricula::factory()->create([
        'establecimiento_id' => $aula->establecimiento_id,
        'establecimiento_periodo_id' => $aula->establecimiento_periodo_id,
        'establecimiento_aula_id' => $aula->id,
        'alumno_id' => $alumno->id,
    ]);
}

function alumnoDeAula(array $escenario, string $nombres, string $apellidos): Alumno
{
    $persona = Persona::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'nombres' => $nombres,
        'apellidos' => $apellidos,
    ]);
    $alumno = Alumno::factory()->create(['persona_id' => $persona->id]);
    matricularAlumnoAsistencia($escenario['aula'], $alumno);

    return $alumno;
}

function abrirAsistencia(array $escenario, int $parcial = 1): void
{
    test()->actingAs($escenario['docente'])
        ->post(route('Docente.asistencia.store'), [
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'modo' => 'asignatura',
            'parcial_numero' => $parcial,
            'destino' => 'asistencia',
        ])
        ->assertRedirect(route('Docente.asistencia.index'));
}

/**
 * @param  array<int, array{estado: string, justificacion?: string|null}>  $filas
 * @return array<string, mixed>
 */
function jornadaAsistencia(array $escenario, array $filas, string $fecha = '2026-10-26', int $parcial = 1): array
{
    return [
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'modo' => $escenario['modo'] ?? 'asignatura',
        'parcial_numero' => $parcial,
        'fecha' => $fecha,
        'asistencias' => $filas,
    ];
}

function lectivo(array $escenario, string $inicio, string $fin): void
{
    $escenario['periodo']->update([
        'fecha_inicio' => $inicio,
        'fecha_fin' => $fin,
    ]);
}

function horaClase(array $escenario, int $dia, string $inicio, string $fin): void
{
    HorarioClase::query()->create([
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'dia_semana' => $dia,
        'hora_inicio' => $inicio,
        'hora_fin' => $fin,
    ]);
}

describe('index', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('Docente.asistencia.index'))
            ->assertRedirect(route('login'));
    });

    it('forbids an administrator from opening the attendance register', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('Docente.asistencia.index'))
            ->assertForbidden();
    });

    it('renders the class roster inside the selected parcial', function () {
        $this->travelTo('2026-10-26 15:00:00');
        $escenario = escenarioAsistencia();
        lectivo($escenario, '2026-09-01', '2027-02-28');
        alumnoDeAula($escenario, 'Luis', 'Andrade');
        abrirAsistencia($escenario);

        $this->get(route('Docente.asistencia.index', ['fecha' => '2026-10-26']))
            ->assertSee('Asistencia diaria')
            ->assertSee('Aulas:')
            ->assertSee('Asignaturas:')
            ->assertSee('Parciales:')
            ->assertSee('Andrade Luis')
            ->assertSee('Falta injustificada')
            ->assertSee('Certificado médico')
            ->assertDontSee('Año lectivo')
            ->assertDontSee('Supera el 10%');
    });

    it('suggests a daily roll for early education', function (string $subnivel) {
        $escenario = escenarioAsistencia(['subnivel' => $subnivel]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.asistencia.index'))
            ->assertSee('\u0022modo\u0022:\u0022jornada\u0022', false);
    })->with([
        'inicial 1' => 'Inicial 1',
        'inicial 2' => 'Inicial 2',
        'preparatoria' => 'Preparatoria',
        'básica elemental' => 'Básica Elemental',
    ]);

    it('suggests a subject roll from básica media onward', function (string $subnivel) {
        $escenario = escenarioAsistencia(['subnivel' => $subnivel]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.asistencia.index'))
            ->assertSee('\u0022modo\u0022:\u0022asignatura\u0022', false)
            ->assertDontSee('\u0022modo\u0022:\u0022jornada\u0022', false);
    })->with([
        'básica media' => 'Básica Media',
        'básica superior' => 'Básica Superior',
        'bachillerato' => 'Bachillerato General Unificado',
    ]);

    it('shows daily attendance inside the insumos menu', function () {
        $this->seed(NavigationSeeder::class);
        $escenario = escenarioAsistencia();
        $insumos = NavigationItem::query()->where('label', 'Insumos')->where('is_group', true)->firstOrFail();

        $this->actingAs($escenario['docente'])
            ->withSession([ActiveRole::SESSION_KEY => Role::Docente->value])
            ->get(route('navigation.hub', $insumos))
            ->assertSee('Asistencia diaria')
            ->assertSee(route('Docente.asistencia.index'), false)
            ->assertSee('una vez al día');
    });

    it('escapes a dangerous student name', function () {
        $this->travelTo('2026-10-26 15:00:00');
        $escenario = escenarioAsistencia();
        lectivo($escenario, '2026-09-01', '2027-02-28');
        alumnoDeAula($escenario, 'Luis', "<script>alert('xss')</script>");
        abrirAsistencia($escenario);

        $this->get(route('Docente.asistencia.index', ['fecha' => '2026-10-26']))
            ->assertDontSee("<script>alert('xss')</script>", false);
    });
});

describe('registrar', function () {
    it('forbids an administrator from recording attendance', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->post(route('Docente.asistencia.registrar'), [])
            ->assertForbidden();
    });

    it('requires the class, the date and every student', function () {
        $escenario = escenarioAsistencia();

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.asistencia.registrar'), [])
            ->assertSessionHasErrors([
                'modo' => 'Elige si la asistencia es del día o de cada asignatura.',
                'establecimiento_aula_id' => 'Selecciona un aula.',
                'establecimiento_asignatura_id' => 'Selecciona una materia.',
                'establecimiento_periodo_ciclo_id' => 'Selecciona un quimestre o trimestre.',
                'parcial_numero' => 'Selecciona un parcial.',
                'fecha' => 'Indica la fecha de la jornada.',
                'asistencias' => 'Registra la asistencia de cada estudiante.',
            ]);
    });

    it('saves the day for the selected parcial and replaces the same class day', function () {
        $this->travelTo('2026-10-26 15:00:00');
        $escenario = escenarioAsistencia();
        lectivo($escenario, '2026-09-01', '2027-02-28');
        $alumno = alumnoDeAula($escenario, 'Luis', 'Andrade');
        $filas = [
            $alumno->id => ['estado' => 'falta'],
        ];

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.asistencia.registrar'), [
                ...jornadaAsistencia($escenario, $filas),
                'establecimiento_id' => 999999,
            ])
            ->assertRedirect(route('Docente.asistencia.index', ['fecha' => '2026-10-26']));

        $guardada = EstablecimientoAsistencia::query()->first();

        expect($guardada)->not->toBeNull()
            ->and($guardada->alumno_id)->toBe($alumno->id)
            ->and($guardada->establecimiento_id)->toBe($escenario['establecimiento']->id)
            ->and($guardada->establecimiento_asignatura_id)->toBe($escenario['malla']->id)
            ->and($guardada->establecimiento_periodo_ciclo_id)->toBe($escenario['ciclo']->id)
            ->and($guardada->parcial_numero)->toBe(1)
            ->and($guardada->fecha->toDateString())->toBe('2026-10-26')
            ->and($guardada->estado)->toBe(EstadoAsistencia::Falta)
            ->and($guardada->justificacion)->toBeNull();

        $this->post(route('Docente.asistencia.registrar'), jornadaAsistencia($escenario, [
            $alumno->id => ['estado' => 'presente'],
        ]))->assertRedirect();

        expect(EstablecimientoAsistencia::query()->count())->toBe(1)
            ->and(EstablecimientoAsistencia::query()->first()?->estado)->toBe(EstadoAsistencia::Presente);
    });

    it('rejects a justified absence without its supporting document', function () {
        $this->travelTo('2026-10-26 15:00:00');
        $escenario = escenarioAsistencia();
        lectivo($escenario, '2026-09-01', '2027-02-28');
        $alumno = alumnoDeAula($escenario, 'Luis', 'Andrade');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.asistencia.registrar'), jornadaAsistencia($escenario, [
                $alumno->id => ['estado' => 'justificada'],
            ]))
            ->assertSessionHasErrors([
                'asistencias.'.$alumno->id.'.justificacion' => 'Indica el documento que justifica la falta.',
            ]);

        $this->assertDatabaseCount('establecimiento_asistencias', 0);
    });

    it('rejects a student who is not on the class roster', function () {
        $this->travelTo('2026-10-26 15:00:00');
        $escenario = escenarioAsistencia();
        lectivo($escenario, '2026-09-01', '2027-02-28');
        alumnoDeAula($escenario, 'Luis', 'Andrade');
        $ajeno = Alumno::factory()->create();

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.asistencia.registrar'), jornadaAsistencia($escenario, [
                $ajeno->id => ['estado' => 'presente'],
            ]))
            ->assertSessionHasErrors([
                'asistencias' => 'La lista de estudiantes no coincide con el aula. Vuelve a cargar la asistencia.',
            ]);

        $this->assertDatabaseCount('establecimiento_asistencias', 0);
    });

    it('returns 404 when the classroom belongs to another teacher', function () {
        $this->travelTo('2026-10-26 15:00:00');
        $escenario = escenarioAsistencia();
        $ajeno = escenarioAsistencia(['grado' => '9no Básica']);
        lectivo($escenario, '2026-09-01', '2027-02-28');
        $alumno = alumnoDeAula($escenario, 'Luis', 'Andrade');

        $this->actingAs($ajeno['docente'])
            ->post(route('Docente.asistencia.registrar'), jornadaAsistencia($escenario, [
                $alumno->id => ['estado' => 'presente'],
            ]))
            ->assertNotFound();
    });

    it('rejects a future class day', function () {
        $this->travelTo('2026-10-26 15:00:00');
        $escenario = escenarioAsistencia();
        lectivo($escenario, '2026-09-01', '2027-02-28');
        $alumno = alumnoDeAula($escenario, 'Luis', 'Andrade');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.asistencia.registrar'), jornadaAsistencia($escenario, [
                $alumno->id => ['estado' => 'presente'],
            ], '2026-10-27'))
            ->assertSessionHasErrors([
                'fecha' => 'No se puede registrar la asistencia de un día que todavía no llega.',
            ]);
    });

    it('rejects a date outside the school year', function () {
        $this->travelTo('2026-10-26 15:00:00');
        $escenario = escenarioAsistencia();
        lectivo($escenario, '2026-09-07', '2026-11-09');
        $alumno = alumnoDeAula($escenario, 'Luis', 'Andrade');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.asistencia.registrar'), jornadaAsistencia($escenario, [
                $alumno->id => ['estado' => 'presente'],
            ], '2026-08-03'))
            ->assertSessionHasErrors([
                'fecha' => 'La fecha está fuera del año lectivo.',
            ]);
    });

    it('rejects a date that is not on the subject schedule', function () {
        $this->travelTo('2026-10-26 15:00:00');
        $escenario = escenarioAsistencia();
        lectivo($escenario, '2026-09-07', '2026-11-09');
        horaClase($escenario, 1, '07:00:00', '07:45:00');
        $alumno = alumnoDeAula($escenario, 'Luis', 'Andrade');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.asistencia.registrar'), jornadaAsistencia($escenario, [
                $alumno->id => ['estado' => 'presente'],
            ], '2026-10-24'))
            ->assertSessionHasErrors([
                'fecha' => 'Esa fecha no tiene hora de clase de esta asignatura. El horario marca el lunes.',
            ]);
    });

    it('records an unjustified absence without showing the annual tally', function () {
        $this->travelTo('2026-10-26 15:00:00');
        $escenario = escenarioAsistencia();
        lectivo($escenario, '2026-10-26', '2026-10-26');
        horaClase($escenario, 1, '07:00:00', '07:45:00');
        $alumno = alumnoDeAula($escenario, 'Luis', 'Andrade');
        abrirAsistencia($escenario);

        $this->post(route('Docente.asistencia.registrar'), jornadaAsistencia($escenario, [
            $alumno->id => ['estado' => 'falta'],
        ]))->assertRedirect();

        $this->get(route('Docente.asistencia.index', ['fecha' => '2026-10-26']))
            ->assertSee('1 faltas injustificadas')
            ->assertDontSee('Año lectivo')
            ->assertDontSee('Supera el 10%');
    });

    it('records one absence when the day has two class periods', function () {
        $this->travelTo('2026-11-09 15:00:00');
        $escenario = escenarioAsistencia();
        lectivo($escenario, '2026-09-07', '2026-11-09');
        horaClase($escenario, 1, '07:00:00', '07:45:00');
        horaClase($escenario, 1, '08:00:00', '08:45:00');
        $alumno = alumnoDeAula($escenario, 'Luis', 'Andrade');
        abrirAsistencia($escenario);

        $this->post(route('Docente.asistencia.registrar'), jornadaAsistencia($escenario, [
            $alumno->id => ['estado' => 'falta'],
        ], '2026-10-26'))->assertRedirect();

        expect(EstablecimientoAsistencia::query()->count())->toBe(1);

        $this->get(route('Docente.asistencia.index', ['fecha' => '2026-10-26']))
            ->assertSee('1 faltas injustificadas')
            ->assertDontSee('Año lectivo');
    });

    it('stores an excused absence or a late mark without an annual tally', function (string $estado, ?string $justificacion) {
        $this->travelTo('2026-10-26 15:00:00');
        $escenario = escenarioAsistencia();
        lectivo($escenario, '2026-10-26', '2026-10-26');
        horaClase($escenario, 1, '07:00:00', '07:45:00');
        $alumno = alumnoDeAula($escenario, 'Luis', 'Andrade');
        abrirAsistencia($escenario);
        $fila = ['estado' => $estado];

        if ($justificacion !== null) {
            $fila['justificacion'] = $justificacion;
        }

        $this->post(route('Docente.asistencia.registrar'), jornadaAsistencia($escenario, [
            $alumno->id => $fila,
        ]))->assertRedirect();

        $guardada = EstablecimientoAsistencia::query()->first();

        expect($guardada?->estado)->toBe(EstadoAsistencia::from($estado))
            ->and($guardada?->justificacion?->value)->toBe($justificacion);

        $this->get(route('Docente.asistencia.index', ['fecha' => '2026-10-26']))
            ->assertDontSee('Año lectivo')
            ->assertDontSee('Supera el 10%');
    })->with([
        'falta justificada' => ['justificada', 'certificado_medico'],
        'atraso' => ['atraso', null],
    ]);

    it('lists the class without an annual tally when past sessions exist', function () {
        $this->travelTo('2026-10-26 15:00:00');
        $escenario = escenarioAsistencia();
        lectivo($escenario, '2026-09-01', '2026-12-15');
        $ausente = alumnoDeAula($escenario, 'Luis', 'Andrade');
        $companero = alumnoDeAula($escenario, 'Mia', 'Bermeo');
        abrirAsistencia($escenario);

        EstablecimientoAsistencia::factory()->create([
            'establecimiento_id' => $escenario['establecimiento']->id,
            'alumno_id' => $ausente->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_aula_id' => $escenario['aula']->id,
            'fecha' => '2026-10-01',
            'estado' => 'falta',
        ]);

        foreach (range(2, 10) as $dia) {
            EstablecimientoAsistencia::factory()->create([
                'establecimiento_id' => $escenario['establecimiento']->id,
                'alumno_id' => $companero->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_aula_id' => $escenario['aula']->id,
                'fecha' => sprintf('2026-10-%02d', $dia),
                'estado' => 'presente',
            ]);
        }

        $this->get(route('Docente.asistencia.index', ['fecha' => '2026-10-26']))
            ->assertSee('Andrade Luis')
            ->assertSee('Bermeo Mia')
            ->assertDontSee('Año lectivo')
            ->assertDontSee('Supera el 10%');
    });

    it('keeps the daily register free of the annual warning when past absences exist', function () {
        $this->travelTo('2026-10-26 15:00:00');
        $escenario = escenarioAsistencia();
        lectivo($escenario, '2026-09-01', '2026-12-15');
        $ausente = alumnoDeAula($escenario, 'Luis', 'Andrade');
        $companero = alumnoDeAula($escenario, 'Mia', 'Bermeo');
        abrirAsistencia($escenario);

        foreach ([1, 2] as $dia) {
            EstablecimientoAsistencia::factory()->create([
                'establecimiento_id' => $escenario['establecimiento']->id,
                'alumno_id' => $ausente->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_aula_id' => $escenario['aula']->id,
                'fecha' => sprintf('2026-10-%02d', $dia),
                'estado' => 'falta',
            ]);
        }

        foreach (range(3, 10) as $dia) {
            EstablecimientoAsistencia::factory()->create([
                'establecimiento_id' => $escenario['establecimiento']->id,
                'alumno_id' => $companero->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_aula_id' => $escenario['aula']->id,
                'fecha' => sprintf('2026-10-%02d', $dia),
                'estado' => 'presente',
            ]);
        }

        $this->get(route('Docente.asistencia.index', ['fecha' => '2026-10-26']))
            ->assertSee('Andrade Luis')
            ->assertDontSee('Año lectivo')
            ->assertDontSee('Supera el 10%');
    });

    it('records one daily roll for the whole classroom and remembers that choice', function () {
        $this->travelTo('2026-10-26 15:00:00');
        $escenario = escenarioAsistencia(['subnivel' => 'Básica Elemental', 'grado' => 'Segundo de EGB']);
        lectivo($escenario, '2026-09-01', '2027-02-28');
        $alumno = alumnoDeAula($escenario, 'Luis', 'Andrade');
        $docente = EstablecimientoAulaDocente::query()
            ->where('establecimiento_aula_id', $escenario['aula']->id)
            ->firstOrFail();
        $otra = $escenario['malla']->replicate();
        $otra->asignatura_id = Sys_Asignatura::factory()->create()->id;
        $otra->save();
        $companera = EstablecimientoAulaDocente::factory()->create([
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $otra->id,
            'empleado_id' => $docente->empleado_id,
        ]);
        $especialista = Empleado::factory()->create();
        $materiaEspecial = $escenario['malla']->replicate();
        $materiaEspecial->asignatura_id = Sys_Asignatura::factory()->create()->id;
        $materiaEspecial->save();
        $ajena = EstablecimientoAulaDocente::factory()->create([
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $materiaEspecial->id,
            'empleado_id' => $especialista->id,
        ]);
        $escenario['modo'] = 'jornada';

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.asistencia.registrar'), jornadaAsistencia($escenario, [
                $alumno->id => ['estado' => 'falta'],
            ]))
            ->assertRedirect(route('Docente.asistencia.index', ['fecha' => '2026-10-26']));

        $guardada = EstablecimientoAsistencia::query()->first();

        expect($guardada)->not->toBeNull()
            ->and($guardada->establecimiento_asignatura_id)->toBeNull()
            ->and($guardada->alcance)->toBe(ModoAsistencia::Jornada)
            ->and($guardada->clave_asignatura)->toBe(0)
            ->and($guardada->parcial_numero)->toBeNull()
            ->and($guardada->estado)->toBe(EstadoAsistencia::Falta)
            ->and($docente->refresh()->modo_asistencia)->toBe(ModoAsistencia::Jornada)
            ->and($companera->refresh()->modo_asistencia)->toBe(ModoAsistencia::Jornada)
            ->and($ajena->refresh()->modo_asistencia)->toBeNull();

        $this->get(route('Docente.asistencia.index', ['fecha' => '2026-10-26']))
            ->assertSee('Andrade Luis')
            ->assertSee('1 faltas injustificadas');
    });

    it('keeps the daily roll separate from the subject roll on the same day', function () {
        $this->travelTo('2026-10-26 15:00:00');
        $escenario = escenarioAsistencia(['subnivel' => 'Básica Elemental']);
        lectivo($escenario, '2026-09-01', '2027-02-28');
        $alumno = alumnoDeAula($escenario, 'Luis', 'Andrade');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.asistencia.registrar'), jornadaAsistencia($escenario, [
                $alumno->id => ['estado' => 'falta'],
            ]))
            ->assertRedirect();

        $escenario['modo'] = 'jornada';

        $this->post(route('Docente.asistencia.registrar'), jornadaAsistencia($escenario, [
            $alumno->id => ['estado' => 'presente'],
        ]))->assertRedirect();

        expect(EstablecimientoAsistencia::query()->count())->toBe(2)
            ->and(EstablecimientoAsistencia::query()->whereNull('establecimiento_asignatura_id')->first()?->estado)->toBe(EstadoAsistencia::Presente)
            ->and(EstablecimientoAsistencia::query()->where('establecimiento_asignatura_id', $escenario['malla']->id)->first()?->estado)->toBe(EstadoAsistencia::Falta);
    });

    it('rejects a daily roll on a weekday without class', function () {
        $this->travelTo('2026-10-28 15:00:00');
        $escenario = escenarioAsistencia(['subnivel' => 'Inicial 2']);
        lectivo($escenario, '2026-09-07', '2026-11-09');
        horaClase($escenario, 1, '07:00:00', '07:45:00');
        $alumno = alumnoDeAula($escenario, 'Luis', 'Andrade');
        $escenario['modo'] = 'jornada';

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.asistencia.registrar'), jornadaAsistencia($escenario, [
                $alumno->id => ['estado' => 'presente'],
            ], '2026-10-27'))
            ->assertSessionHasErrors([
                'fecha' => 'Esa fecha no tiene clase en esta aula. El horario marca el lunes.',
            ]);

        $this->assertDatabaseCount('establecimiento_asistencias', 0);
    });
});
