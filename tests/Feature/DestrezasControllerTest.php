<?php

use App\Auth\ActiveRole;
use App\Enums\CalificacionDestreza;
use App\Enums\EsquemaCiclo;
use App\Enums\Role;
use App\Enums\TipoAsignatura;
use App\Enums\TipoCalificacion;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoIndicadorCalificacion;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\NavigationItem;
use App\Models\Persona;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Destreza;
use App\Models\Sys_IndicadorEvaluacion;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

/**
 * @return array<string, mixed>
 */
function escenarioDestrezas(bool $elemental = false): array
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
    $catalogo->subnivel->update([
        'tipo_calificacion' => TipoCalificacion::Cualitativa,
        'califica_por_destrezas' => true,
        'equivale_nota_nacional' => $elemental,
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
    $asignatura = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $catalogo->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Lengua y Literatura'])->id,
        'tipo_calificacion' => TipoCalificacion::Cualitativa,
        'califica_por_destrezas' => true,
        'tipo_asignatura' => TipoAsignatura::TroncoComun,
        'es_flotante' => false,
    ]);
    EstablecimientoAulaDocente::factory()->create([
        'establecimiento_aula_id' => $aula->id,
        'establecimiento_asignatura_id' => $asignatura->id,
        'empleado_id' => $empleado->id,
    ]);
    $ciclo = EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'orden' => 1,
        'nombre' => 'Primer quimestre',
    ]);
    $escenario = compact('establecimiento', 'docente', 'aula', 'asignatura', 'ciclo');

    $destreza = Sys_Destreza::factory()->create([
        'asignatura_id' => $escenario['asignatura']->asignatura_id,
        'grado_id' => $escenario['asignatura']->grado_id,
        'codigo' => 'LL.1',
        'descripcion' => 'Comunicar datos de su identidad',
    ]);
    $indicador = Sys_IndicadorEvaluacion::factory()->create([
        'destreza_id' => $destreza->id,
        'codigo' => 'I.1',
        'descripcion' => 'Dice su nombre completo',
    ]);

    return [
        ...$escenario,
        'destreza' => $destreza,
        'indicador' => $indicador,
    ];
}

function alumnoDeDestrezas(array $escenario, string $nombres, string $apellidos): Alumno
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

function abrirDestrezas(array $escenario): void
{
    test()->actingAs($escenario['docente'])
        ->post(route('Docente.destrezas.store'), [
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['asignatura']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        ])
        ->assertRedirect(route('Docente.destrezas.index'));
}

describe('index', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('Docente.destrezas.index'))
            ->assertRedirect(route('login'));
    });

    it('forbids an administrator from opening the skills register', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('Docente.destrezas.index'))
            ->assertForbidden();
    });

    it('renders the qualitative scale and the curriculum indicators', function () {
        $escenario = escenarioDestrezas();
        alumnoDeDestrezas($escenario, 'Luis', 'Andrade');
        abrirDestrezas($escenario);

        $this->get(route('Docente.destrezas.index'))
            ->assertOk()
            ->assertSee('Destrezas')
            ->assertSee('Andrade Luis')
            ->assertSee('Comunicar datos de su identidad')
            ->assertSee('Dice su nombre completo')
            ->assertSee('Destreza o aprendizaje alcanzado')
            ->assertSee('No evaluado')
            ->assertDontSee('nota sobre 10 equivalente');
    });

    it('asks sistemas to load indicators when the subject has none', function () {
        $escenario = escenarioDestrezas();
        $escenario['indicador']->delete();
        $escenario['destreza']->delete();
        alumnoDeDestrezas($escenario, 'Luis', 'Andrade');
        abrirDestrezas($escenario);

        $this->get(route('Docente.destrezas.index'))
            ->assertSee('Esta asignatura no tiene destrezas ni indicadores para el grado.');
    });

    it('shows the skills register inside the insumos menu', function () {
        $this->seed(NavigationSeeder::class);
        $escenario = escenarioDestrezas();
        $insumos = NavigationItem::query()->where('label', 'Insumos')->where('is_group', true)->firstOrFail();

        $this->actingAs($escenario['docente'])
            ->withSession([ActiveRole::SESSION_KEY => Role::Docente->value])
            ->get(route('navigation.hub', $insumos))
            ->assertSee('Destrezas')
            ->assertSee(route('Docente.destrezas.index'), false)
            ->assertSee('A, EP, I o NE');
    });

    it('escapes a dangerous indicator description', function () {
        $escenario = escenarioDestrezas();
        $escenario['indicador']->update(['descripcion' => "<script>alert('xss')</script>"]);
        alumnoDeDestrezas($escenario, 'Luis', 'Andrade');
        abrirDestrezas($escenario);

        $this->get(route('Docente.destrezas.index'))
            ->assertDontSee("<script>alert('xss')</script>", false);
    });
});

describe('registrar', function () {
    it('forbids an administrator from recording skills', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->post(route('Docente.destrezas.registrar'), [])
            ->assertForbidden();
    });

    it('requires the classroom, the cycle and a scale value', function () {
        $escenario = escenarioDestrezas();

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.destrezas.registrar'), [])
            ->assertSessionHasErrors([
                'establecimiento_aula_id' => 'Selecciona un aula.',
                'establecimiento_asignatura_id' => 'Selecciona una asignatura.',
                'establecimiento_periodo_ciclo_id' => 'Selecciona un quimestre o trimestre.',
                'destrezas' => 'Registra la destreza de cada estudiante.',
            ]);
    });

    it('rejects a subject that is not graded by skills', function () {
        $escenario = escenarioDestrezas();
        $escenario['asignatura']->update(['califica_por_destrezas' => false]);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.destrezas.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['asignatura']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            ])
            ->assertSessionHasErrors([
                'establecimiento_asignatura_id' => 'Esta asignatura no se evalúa por destrezas.',
            ]);
    });

    it('rejects a teacher from another school', function () {
        $escenario = escenarioDestrezas();
        $alumno = alumnoDeDestrezas($escenario, 'Luis', 'Andrade');
        $ajeno = escenarioDestrezas();

        $this->actingAs($ajeno['docente'])
            ->post(route('Docente.destrezas.registrar'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['asignatura']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'destrezas' => [
                    $alumno->id => [
                        $escenario['indicador']->id => ['calificacion' => CalificacionDestreza::Alcanzado->value],
                    ],
                ],
            ])
            ->assertNotFound();
    });

    it('stores the qualitative indicator grade', function () {
        $escenario = escenarioDestrezas();
        $alumno = alumnoDeDestrezas($escenario, 'Luis', 'Andrade');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.destrezas.registrar'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['asignatura']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'destrezas' => [
                    $alumno->id => [
                        $escenario['indicador']->id => [
                            'calificacion' => CalificacionDestreza::EnProceso->value,
                            'observacion' => 'Acompaña el reconocimiento de su nombre.',
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('Docente.destrezas.index'))
            ->assertSessionHas('status', 'destrezas-registradas');

        $registro = EstablecimientoIndicadorCalificacion::query()->firstOrFail();

        expect($registro->calificacion)->toBe(CalificacionDestreza::EnProceso)
            ->and($registro->observacion)->toBe('Acompaña el reconocimiento de su nombre.')
            ->and($registro->alumno_id)->toBe($alumno->id);
    });

    it('shows the reached skill on the learning report without a numeric average', function () {
        $escenario = escenarioLibreta();
        $escenario['malla']->update([
            'tipo_calificacion' => TipoCalificacion::Cualitativa,
            'califica_por_destrezas' => true,
        ]);
        $escenario['aula']->load('establecimientoGrado.grado.subnivel');
        $escenario['aula']->establecimientoGrado->grado->subnivel->update([
            'califica_por_destrezas' => true,
            'equivale_nota_nacional' => false,
        ]);
        $destreza = Sys_Destreza::factory()->create([
            'asignatura_id' => $escenario['malla']->asignatura_id,
            'grado_id' => $escenario['malla']->grado_id,
            'codigo' => 'LL.1',
            'descripcion' => 'Comunicar datos de su identidad',
        ]);
        $indicador = Sys_IndicadorEvaluacion::factory()->create([
            'destreza_id' => $destreza->id,
            'codigo' => 'I.1',
            'descripcion' => 'Dice su nombre completo',
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Herrera');
        matricularAlumnoLibreta($escenario['aula'], $alumno);
        EstablecimientoIndicadorCalificacion::factory()->create([
            'establecimiento_id' => $escenario['establecimiento']->id,
            'alumno_id' => $alumno->id,
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'indicador_evaluacion_id' => $indicador->id,
            'calificacion' => CalificacionDestreza::Alcanzado,
        ]);

        $this->actingAs($escenario['secretaria'])
            ->withSession(sesionLibreta($escenario))
            ->get(route('secretaria.informes-aprendizaje.index', [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'tipo' => 'ciclo',
            ]))
            ->assertSee('Comunicar datos de su identidad')
            ->assertSee('Destreza o aprendizaje alcanzado')
            ->assertSee('No se promedia una nota sobre 10')
            ->assertDontSee('Escala de calificaciones (Art. 194)');
    });

    it('converts elemental skills into the national grade used for promotion', function () {
        $escenario = escenarioLibreta();
        $escenario['malla']->update([
            'tipo_calificacion' => TipoCalificacion::Cualitativa,
            'califica_por_destrezas' => true,
        ]);
        $escenario['aula']->load('establecimientoGrado.grado.subnivel');
        $escenario['aula']->establecimientoGrado->grado->subnivel->update([
            'califica_por_destrezas' => true,
            'equivale_nota_nacional' => true,
        ]);
        $destreza = Sys_Destreza::factory()->create([
            'asignatura_id' => $escenario['malla']->asignatura_id,
            'grado_id' => $escenario['malla']->grado_id,
            'codigo' => 'M.1',
            'descripcion' => 'Contar objetos de su entorno',
        ]);
        $indicador = Sys_IndicadorEvaluacion::factory()->create([
            'destreza_id' => $destreza->id,
            'descripcion' => 'Cuenta hasta diez',
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Herrera');
        matricularAlumnoLibreta($escenario['aula'], $alumno);
        EstablecimientoIndicadorCalificacion::factory()->create([
            'establecimiento_id' => $escenario['establecimiento']->id,
            'alumno_id' => $alumno->id,
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'indicador_evaluacion_id' => $indicador->id,
            'calificacion' => CalificacionDestreza::Iniciado,
        ]);

        $this->actingAs($escenario['secretaria'])
            ->withSession(sesionLibreta($escenario))
            ->get(route('secretaria.certificado-promocion.index', [
                'establecimiento_aula_id' => $escenario['aula']->id,
            ]))
            ->assertSee('5.00')
            ->assertSee('Destreza o aprendizaje iniciado')
            ->assertSee('No reúne los requisitos de promoción')
            ->assertSee('Contar objetos de su entorno');
    });
});
