<?php

use App\Enums\EsquemaCiclo;
use App\Enums\EstadoAsistencia;
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
use App\Models\EstablecimientoCalificacion;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\EstablecimientoInsumoActividadCalificacion;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\Persona;
use App\Models\Sys_Asignatura;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * @return array{
 *     establecimiento: Establecimiento,
 *     docente: User,
 *     aula: EstablecimientoAula,
 *     malla: EstablecimientoAsignatura,
 *     ciclo: EstablecimientoPeriodoCiclo,
 *     alumno: Alumno
 * }
 */
function escenarioDiagnosticoEstudiante(): array
{
    $establecimiento = Establecimiento::factory()->create([
        'gemini_api_key' => 'clave-diagnostico',
        'gemini_model' => 'gemini-diagnostico',
        'gemini_preguntas_limite' => 100,
        'gemini_tokens_limite' => 1000,
    ]);
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
        'fecha_inicio' => '2026-09-01',
        'fecha_fin' => '2027-06-30',
        'abierto_en' => now(),
    ]);
    ['grado' => $catalogo] = catalogoEstructura([
        'nivel' => 'Nivel '.fake()->unique()->numerify('####'),
        'subnivel' => 'Subnivel '.fake()->unique()->numerify('####'),
        'grado' => '8vo '.fake()->unique()->numerify('####'),
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
    $alumno = alumnoDelEstablecimiento($establecimiento, 'Cárdenas');
    EstablecimientoMatricula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_aula_id' => $aula->id,
        'alumno_id' => $alumno->id,
    ]);

    return compact('establecimiento', 'docente', 'aula', 'malla', 'ciclo', 'alumno');
}

function guardarContextoDiagnostico(array $escenario): void
{
    test()->actingAs($escenario['docente'])
        ->post(route('Docente.diagnostico.seleccionar'), [
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'parcial_numero' => 1,
            'destino' => 'diagnostico',
        ])
        ->assertRedirect(route('Docente.diagnostico.index'));
}

function fakeDiagnosticoGemini(string $texto = '', int $tokens = 40): void
{
    $cuerpo = $texto !== '' ? $texto : json_encode([
        'resumen' => 'La nota bajó de 9 a 5 y hay faltas reiteradas.',
        'riesgo_reprobar' => 'alto',
        'tendencias' => ['Tendencia a la baja en los insumos'],
        'alertas' => ['Riesgo de reprobar Matemática'],
        'recomendaciones' => ['Refuerzo en fracciones'],
    ], JSON_UNESCAPED_UNICODE);

    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => $cuerpo],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'totalTokenCount' => $tokens,
            ],
        ]),
    ]);
}

function alumnoMatriculadoEnDiagnostico(array $escenario, string $apellidos): Alumno
{
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], $apellidos);
    EstablecimientoMatricula::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'establecimiento_periodo_id' => $escenario['aula']->establecimiento_periodo_id,
        'establecimiento_aula_id' => $escenario['aula']->id,
        'alumno_id' => $alumno->id,
    ]);

    return $alumno;
}

/**
 * @param  array<int, list<string>>  $notasPorAlumno
 */
function calificarInsumosDeDiagnostico(array $escenario, array $notasPorAlumno): void
{
    $empleadoId = EstablecimientoAulaDocente::query()
        ->where('establecimiento_aula_id', $escenario['aula']->id)
        ->value('empleado_id');
    $cantidad = max(array_map('count', $notasPorAlumno));
    $actividades = [];

    for ($indice = 0; $indice < $cantidad; $indice++) {
        $actividades[] = EstablecimientoInsumoActividad::factory()->create([
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'empleado_id' => $empleadoId,
            'nombre' => 'Taller '.($indice + 1),
            'created_at' => sprintf('2026-%02d-10 08:00:00', 9 + $indice),
        ]);
    }

    foreach ($notasPorAlumno as $alumnoId => $notas) {
        foreach ($notas as $indice => $nota) {
            EstablecimientoInsumoActividadCalificacion::factory()->create([
                'establecimiento_insumo_actividad_id' => $actividades[$indice]->id,
                'alumno_id' => $alumnoId,
                'nota' => $nota,
            ]);
        }
    }
}

function fakeDiagnosticoGrupoGemini(string $texto = '', int $tokens = 55): void
{
    fakeDiagnosticoGemini($texto !== '' ? $texto : json_encode([
        'resumen' => 'Hay un estudiante con bajo rendimiento.',
        'estudiantes' => [
            [
                'estudiante' => 'Cárdenas',
                'riesgo_reprobar' => 'alto',
                'problemas' => ['Notas bajo el mínimo'],
                'recomendaciones' => ['Plan de refuerzo en fracciones'],
            ],
        ],
        'recomendaciones' => ['Revisar el ritmo de la clase'],
    ], JSON_UNESCAPED_UNICODE), $tokens);
}

it('redirects guests from the teacher diagnosis page to the login page', function (string $method, string $routeName) {
    $this->{$method}(route($routeName))
        ->assertRedirect(route('login'));
})->with([
    'open page' => ['get', 'Docente.diagnostico.index'],
    'select class' => ['post', 'Docente.diagnostico.seleccionar'],
    'analyze student' => ['post', 'Docente.diagnostico.store'],
    'analyze group' => ['post', 'Docente.diagnostico.grupo'],
]);

it('forbids other roles from opening the teacher diagnosis page', function (User $user) {
    $this->actingAs($user)
        ->get(route('Docente.diagnostico.index'))
        ->assertForbidden();
})->with([
    'administrator' => fn () => adminOf(Establecimiento::factory()->create()),
    'secretary' => fn () => secretariaOf(Establecimiento::factory()->create()),
]);

it('lists the classroom students after the teacher selects a class', function () {
    $escenario = escenarioDiagnosticoEstudiante();
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->get(route('Docente.diagnostico.index'))
        ->assertOk()
        ->assertSee('Diagnóstico del estudiante')
        ->assertSee('Cárdenas')
        ->assertSee('Analizar con IA')
        ->assertSee('Analizar alumnos con problemas');
});

it('sends the student grades and attendance to Gemini and shows the diagnosis', function () {
    $escenario = escenarioDiagnosticoEstudiante();
    $empleadoId = EstablecimientoAulaDocente::query()
        ->where('establecimiento_aula_id', $escenario['aula']->id)
        ->value('empleado_id');
    $primera = EstablecimientoInsumoActividad::factory()->create([
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'empleado_id' => $empleadoId,
        'nombre' => 'Taller 1',
        'created_at' => '2026-09-10 08:00:00',
    ]);
    $segunda = EstablecimientoInsumoActividad::factory()->create([
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'empleado_id' => $empleadoId,
        'nombre' => 'Taller 2',
        'created_at' => '2026-10-10 08:00:00',
    ]);
    EstablecimientoInsumoActividadCalificacion::factory()->create([
        'establecimiento_insumo_actividad_id' => $primera->id,
        'alumno_id' => $escenario['alumno']->id,
        'nota' => '9.00',
    ]);
    EstablecimientoInsumoActividadCalificacion::factory()->create([
        'establecimiento_insumo_actividad_id' => $segunda->id,
        'alumno_id' => $escenario['alumno']->id,
        'nota' => '5.00',
    ]);
    EstablecimientoCalificacion::factory()->create([
        'establecimiento_periodo_id' => $escenario['aula']->establecimiento_periodo_id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'alumno_id' => $escenario['alumno']->id,
        'ciclo_orden' => 1,
        'parcial_numero' => 1,
        'nota' => '6.50',
    ]);
    EstablecimientoAsistencia::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'alumno_id' => $escenario['alumno']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_aula_id' => $escenario['aula']->id,
        'fecha' => '2026-10-05',
        'estado' => EstadoAsistencia::Falta,
    ]);
    EstablecimientoAsistencia::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'alumno_id' => $escenario['alumno']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_aula_id' => $escenario['aula']->id,
        'fecha' => '2026-10-06',
        'estado' => EstadoAsistencia::Presente,
    ]);
    fakeDiagnosticoGemini();
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->from(route('Docente.diagnostico.index'))
        ->post(route('Docente.diagnostico.store'), [
            'alumno_id' => $escenario['alumno']->id,
        ])
        ->assertRedirect(route('Docente.diagnostico.index'))
        ->assertSessionHas('diagnostico.resumen', 'La nota bajó de 9 a 5 y hay faltas reiteradas.')
        ->assertSessionHas('diagnostico.riesgo_reprobar', 'alto')
        ->assertSessionHas('diagnostico.recomendaciones', ['Refuerzo en fracciones']);

    Http::assertSent(function (Request $request) {
        $prompt = $request['contents'][0]['parts'][0]['text'] ?? '';
        $sistema = $request['system_instruction']['parts'][0]['text'] ?? '';

        return $request->hasHeader('x-goog-api-key', 'clave-diagnostico')
            && str_contains($request->url(), 'models/gemini-diagnostico:generateContent')
            && str_contains($sistema, 'asesor académico')
            && str_contains($prompt, '9.00')
            && str_contains($prompt, '5.00')
            && str_contains($prompt, '6.50')
            && str_contains($prompt, 'Falta')
            && str_contains($prompt, 'Presente')
            && str_contains($prompt, 'Cárdenas')
            && str_contains($prompt, 'notas_insumo')
            && str_contains($prompt, 'asistencias');
    });

    $escenario['establecimiento']->refresh();

    expect($escenario['establecimiento']->gemini_preguntas_usadas)->toBe(1)
        ->and($escenario['establecimiento']->gemini_tokens_usados)->toBe(40);

    $this->actingAs($escenario['docente'])
        ->get(route('Docente.diagnostico.index'))
        ->assertSee('La nota bajó de 9 a 5 y hay faltas reiteradas.')
        ->assertSee('Riesgo alto')
        ->assertSee('Refuerzo en fracciones');
});

it('does not diagnose a student from another school', function () {
    $escenario = escenarioDiagnosticoEstudiante();
    $ajeno = escenarioDiagnosticoEstudiante();
    fakeDiagnosticoGemini();
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->post(route('Docente.diagnostico.store'), [
            'alumno_id' => $ajeno['alumno']->id,
        ])
        ->assertNotFound();

    Http::assertNothingSent();
});

it('does not diagnose a student outside the selected classroom', function () {
    $escenario = escenarioDiagnosticoEstudiante();
    $otro = alumnoDelEstablecimiento($escenario['establecimiento'], 'Benítez');
    fakeDiagnosticoGemini();
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->post(route('Docente.diagnostico.store'), [
            'alumno_id' => $otro->id,
        ])
        ->assertNotFound();

    Http::assertNothingSent();
});

it('does not call Gemini when the school has no key', function () {
    Http::preventStrayRequests();
    $escenario = escenarioDiagnosticoEstudiante();
    $escenario['establecimiento']->update(['gemini_api_key' => null]);
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->from(route('Docente.diagnostico.index'))
        ->post(route('Docente.diagnostico.store'), [
            'alumno_id' => $escenario['alumno']->id,
        ])
        ->assertRedirect(route('Docente.diagnostico.index'))
        ->assertSessionHas('error', 'Este instituto no tiene configurada la clave de Gemini. Pide al administrador que la ingrese en Configuración.');

    Http::assertNothingSent();
});

it('does not call Gemini when the school used every question this month', function () {
    Http::preventStrayRequests();
    $this->travelTo('2026-10-20 12:00:00');
    $escenario = escenarioDiagnosticoEstudiante();
    $escenario['establecimiento']->update([
        'gemini_preguntas_limite' => 2,
        'gemini_preguntas_usadas' => 2,
        'gemini_tokens_usados' => 10,
        'gemini_uso_mes' => '2026-10',
    ]);
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->from(route('Docente.diagnostico.index'))
        ->post(route('Docente.diagnostico.store'), [
            'alumno_id' => $escenario['alumno']->id,
        ])
        ->assertRedirect(route('Docente.diagnostico.index'))
        ->assertSessionHas('error', 'Este instituto ya no tiene preguntas disponibles este mes. Pide al administrador que aumente el cupo en Configuración → Asistente.');

    Http::assertNothingSent();
});

it('asks the teacher to choose a student', function () {
    $escenario = escenarioDiagnosticoEstudiante();
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->from(route('Docente.diagnostico.index'))
        ->post(route('Docente.diagnostico.store'), [])
        ->assertRedirect(route('Docente.diagnostico.index'))
        ->assertSessionHasErrors(['alumno_id' => 'Selecciona un estudiante.']);
});

it('shows the raw Gemini text when the diagnosis is not JSON', function () {
    $escenario = escenarioDiagnosticoEstudiante();
    fakeDiagnosticoGemini('El estudiante necesita refuerzo inmediato.');
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->from(route('Docente.diagnostico.index'))
        ->post(route('Docente.diagnostico.store'), [
            'alumno_id' => $escenario['alumno']->id,
        ])
        ->assertRedirect(route('Docente.diagnostico.index'))
        ->assertSessionHas('diagnostico.resumen', 'El estudiante necesita refuerzo inmediato.')
        ->assertSessionHas('diagnostico.riesgo_reprobar', 'desconocido');
});

it('escapes a dangerous diagnosis returned by Gemini', function () {
    $escenario = escenarioDiagnosticoEstudiante();
    fakeDiagnosticoGemini(json_encode([
        'resumen' => "<script>alert('xss')</script>",
        'riesgo_reprobar' => 'alto',
        'tendencias' => [],
        'alertas' => [],
        'recomendaciones' => [],
    ]));
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->from(route('Docente.diagnostico.index'))
        ->post(route('Docente.diagnostico.store'), [
            'alumno_id' => $escenario['alumno']->id,
        ]);

    $this->actingAs($escenario['docente'])
        ->get(route('Docente.diagnostico.index'))
        ->assertSee("<script>alert('xss')</script>")
        ->assertDontSee("<script>alert('xss')</script>", false);
});

it('escapes a dangerous student name on the diagnosis page', function () {
    $escenario = escenarioDiagnosticoEstudiante();
    $escenario['alumno']->persona->update([
        'apellidos' => "<script>alert('xss')</script>",
        'nombres' => 'Ana',
    ]);
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->get(route('Docente.diagnostico.index'))
        ->assertSee("<script>alert('xss')</script>")
        ->assertDontSee("<script>alert('xss')</script>", false);
});

it('sends only at-risk students to Gemini and shows group recommendations', function () {
    $escenario = escenarioDiagnosticoEstudiante();
    $sano = alumnoMatriculadoEnDiagnostico($escenario, 'Benítez');
    calificarInsumosDeDiagnostico($escenario, [
        $escenario['alumno']->id => ['5.00'],
        $sano->id => ['9.00'],
    ]);
    fakeDiagnosticoGrupoGemini();
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->from(route('Docente.diagnostico.index'))
        ->post(route('Docente.diagnostico.grupo'))
        ->assertRedirect(route('Docente.diagnostico.index'))
        ->assertSessionHas('diagnostico_grupo.resumen', 'Hay un estudiante con bajo rendimiento.')
        ->assertSessionHas('diagnostico_grupo.recomendaciones', ['Revisar el ritmo de la clase']);

    Http::assertSent(function (Request $request) {
        $prompt = $request['contents'][0]['parts'][0]['text'] ?? '';

        return str_contains($prompt, 'estudiantes_con_dificultades')
            && str_contains($prompt, 'Cárdenas')
            && str_contains($prompt, '5.00')
            && ! str_contains($prompt, 'Benítez')
            && ! str_contains($prompt, '9.00');
    });

    $escenario['establecimiento']->refresh();

    expect($escenario['establecimiento']->gemini_preguntas_usadas)->toBe(1)
        ->and($escenario['establecimiento']->gemini_tokens_usados)->toBe(55);

    $this->actingAs($escenario['docente'])
        ->get(route('Docente.diagnostico.index'))
        ->assertSee('Alumnos con problemas')
        ->assertSee('Hay un estudiante con bajo rendimiento.')
        ->assertSee('Plan de refuerzo en fracciones')
        ->assertSee('Revisar el ritmo de la clase');
});

it('does not call Gemini when nobody in the classroom is at risk', function () {
    Http::preventStrayRequests();
    $escenario = escenarioDiagnosticoEstudiante();
    calificarInsumosDeDiagnostico($escenario, [
        $escenario['alumno']->id => ['9.00'],
    ]);
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->from(route('Docente.diagnostico.index'))
        ->post(route('Docente.diagnostico.grupo'))
        ->assertRedirect(route('Docente.diagnostico.index'))
        ->assertSessionHas('status', 'grupo-sin-problemas');

    Http::assertNothingSent();

    $this->actingAs($escenario['docente'])
        ->get(route('Docente.diagnostico.index'))
        ->assertSee('Ningún estudiante de este paralelo muestra señales de dificultad en notas o asistencia.');
});

it('asks the teacher to choose a class before analyzing students with problems', function () {
    Http::preventStrayRequests();
    $escenario = escenarioDiagnosticoEstudiante();

    $this->actingAs($escenario['docente'])
        ->from(route('Docente.diagnostico.index'))
        ->post(route('Docente.diagnostico.grupo'))
        ->assertRedirect(route('Docente.diagnostico.index'))
        ->assertSessionHasErrors(['grupo' => 'Selecciona un aula y una materia para analizar al paralelo.']);

    Http::assertNothingSent();
});

it('does not analyze an empty classroom with AI', function () {
    Http::preventStrayRequests();
    $escenario = escenarioDiagnosticoEstudiante();
    EstablecimientoMatricula::query()->where('alumno_id', $escenario['alumno']->id)->delete();
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->from(route('Docente.diagnostico.index'))
        ->post(route('Docente.diagnostico.grupo'))
        ->assertRedirect(route('Docente.diagnostico.index'))
        ->assertSessionHasErrors(['grupo' => 'No hay alumnos matriculados en este aula o asignatura.']);

    Http::assertNothingSent();
});

it('does not call Gemini for the group when the school has no key', function () {
    Http::preventStrayRequests();
    $escenario = escenarioDiagnosticoEstudiante();
    $escenario['establecimiento']->update(['gemini_api_key' => null]);
    calificarInsumosDeDiagnostico($escenario, [
        $escenario['alumno']->id => ['5.00'],
    ]);
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->from(route('Docente.diagnostico.index'))
        ->post(route('Docente.diagnostico.grupo'))
        ->assertRedirect(route('Docente.diagnostico.index'))
        ->assertSessionHas('error', 'Este instituto no tiene configurada la clave de Gemini. Pide al administrador que la ingrese en Configuración.');

    Http::assertNothingSent();
});

it('does not call Gemini for the group when the school used every question this month', function () {
    Http::preventStrayRequests();
    $this->travelTo('2026-10-20 12:00:00');
    $escenario = escenarioDiagnosticoEstudiante();
    $escenario['establecimiento']->update([
        'gemini_preguntas_limite' => 2,
        'gemini_preguntas_usadas' => 2,
        'gemini_tokens_usados' => 10,
        'gemini_uso_mes' => '2026-10',
    ]);
    calificarInsumosDeDiagnostico($escenario, [
        $escenario['alumno']->id => ['5.00'],
    ]);
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->from(route('Docente.diagnostico.index'))
        ->post(route('Docente.diagnostico.grupo'))
        ->assertRedirect(route('Docente.diagnostico.index'))
        ->assertSessionHas('error', 'Este instituto ya no tiene preguntas disponibles este mes. Pide al administrador que aumente el cupo en Configuración → Asistente.');

    Http::assertNothingSent();
});

it('escapes a dangerous group diagnosis returned by Gemini', function () {
    $escenario = escenarioDiagnosticoEstudiante();
    calificarInsumosDeDiagnostico($escenario, [
        $escenario['alumno']->id => ['5.00'],
    ]);
    fakeDiagnosticoGrupoGemini(json_encode([
        'resumen' => "<script>alert('grupo')</script>",
        'estudiantes' => [
            [
                'estudiante' => "<script>alert('nombre')</script>",
                'riesgo_reprobar' => 'alto',
                'problemas' => ["<script>alert('problema')</script>"],
                'recomendaciones' => ["<script>alert('rec')</script>"],
            ],
        ],
        'recomendaciones' => ["<script>alert('grupo-rec')</script>"],
    ]));
    guardarContextoDiagnostico($escenario);

    $this->actingAs($escenario['docente'])
        ->from(route('Docente.diagnostico.index'))
        ->post(route('Docente.diagnostico.grupo'));

    $this->actingAs($escenario['docente'])
        ->get(route('Docente.diagnostico.index'))
        ->assertSee("<script>alert('grupo')</script>")
        ->assertDontSee("<script>alert('grupo')</script>", false)
        ->assertSee("<script>alert('nombre')</script>")
        ->assertDontSee("<script>alert('nombre')</script>", false)
        ->assertSee("<script>alert('problema')</script>")
        ->assertDontSee("<script>alert('problema')</script>", false)
        ->assertSee("<script>alert('rec')</script>")
        ->assertDontSee("<script>alert('rec')</script>", false)
        ->assertSee("<script>alert('grupo-rec')</script>")
        ->assertDontSee("<script>alert('grupo-rec')</script>", false);
});
