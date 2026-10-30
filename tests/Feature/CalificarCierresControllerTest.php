<?php

use App\Auth\ActiveRole;
use App\Enums\EsquemaCiclo;
use App\Enums\Role;
use App\Enums\TipoAsignatura;
use App\Enums\TipoCalificacion;
use App\Enums\TipoCierreEvaluacion;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaAsignaturaCiclo;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoCierreActividad;
use App\Models\EstablecimientoCierreActividadCalificacion;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\EstablecimientoPeriodoConfiguracionGrado;
use App\Models\Persona;
use App\Models\Sys_Asignatura;
use App\Models\User;
use App\Services\LibretaAula;
use Database\Seeders\NavigationSeeder;
use Illuminate\Support\Facades\Exceptions;

/**
 * @return array{
 *     establecimiento: Establecimiento,
 *     docente: User,
 *     aula: EstablecimientoAula,
 *     malla: EstablecimientoAsignatura,
 *     ciclo: EstablecimientoPeriodoCiclo
 * }
 */
function escenarioCalificarCierres(array $atributos = []): array
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
        'subnivel' => 'Subnivel '.fake()->unique()->numerify('####'),
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
        'porcentaje_insumos' => $atributos['porcentaje_insumos'] ?? '70.00',
        'porcentaje_examen' => $atributos['porcentaje_examen'] ?? '30.00',
        'porcentaje_proyecto' => $atributos['porcentaje_proyecto'] ?? '0.00',
    ]);

    return compact('establecimiento', 'docente', 'aula', 'malla', 'ciclo', 'periodo');
}

function guardarContextoCierre(array $escenario, string $ruta = 'Docente.examenes.store'): void
{
    test()->actingAs($escenario['docente'])
        ->post(route($ruta), [
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        ]);
}

function matricularAlumnoCierre(EstablecimientoAula $aula, Alumno $alumno): EstablecimientoMatricula
{
    return EstablecimientoMatricula::factory()->create([
        'establecimiento_id' => $aula->establecimiento_id,
        'establecimiento_periodo_id' => $aula->establecimiento_periodo_id,
        'establecimiento_aula_id' => $aula->id,
        'alumno_id' => $alumno->id,
    ]);
}

function configurarCierrePorAsignatura(array $escenario, string $examen, string $proyecto): void
{
    EstablecimientoAulaAsignaturaCiclo::factory()->create([
        'establecimiento_periodo_id' => $escenario['aula']->establecimiento_periodo_id,
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'ciclo_orden' => $escenario['ciclo']->orden,
        'porcentaje_insumos' => '50.00',
        'porcentaje_examen' => $examen,
        'porcentaje_proyecto' => $proyecto,
    ]);
}

function configurarCierrePorGrado(
    array $escenario,
    string $examen,
    string $proyecto,
    string $examenFinal = '0.00',
    string $proyectoFinal = '0.00',
): void {
    $escenario['aula']->loadMissing('establecimientoGrado');
    $configuracion = EstablecimientoPeriodoConfiguracionGrado::query()->create([
        'establecimiento_periodo_id' => $escenario['aula']->establecimiento_periodo_id,
        'nombre' => 'Configuración del grado',
        'porcentaje_examen_final' => $examenFinal,
        'porcentaje_proyecto_final' => $proyectoFinal,
    ]);
    $configuracion->grados()->sync([
        $escenario['aula']->establecimientoGrado->grado_id => [
            'establecimiento_periodo_id' => $escenario['aula']->establecimiento_periodo_id,
        ],
    ]);
    $configuracion->ciclos()->create([
        'ciclo_orden' => $escenario['ciclo']->orden,
        'porcentaje_periodo' => '50.00',
        'porcentaje_insumos' => '50.00',
        'porcentaje_examen' => $examen,
        'porcentaje_proyecto' => $proyecto,
    ]);
}

describe('index', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('Docente.examenes.index'))
            ->assertRedirect(route('login'));
    });

    it('forbids an administrator from opening the exam screen', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('Docente.examenes.index'))
            ->assertForbidden();
    });

    it('lets a teacher open the exam and project screens', function () {
        $escenario = escenarioCalificarCierres();

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.examenes.index'))
            ->assertSee('Calificar Exámenes')
            ->assertSee('requiereParcial: false', false)
            ->assertSee('requiereCiclo: true', false);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.proyectos.index'))
            ->assertSee('Calificar Proyectos');

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.examenes-finales.index'))
            ->assertSee('Calificar Exámenes finales')
            ->assertSee('requiereCiclo: false', false);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.proyectos-finales.index'))
            ->assertSee('Calificar Proyectos finales');
    });

    it('opens the exam form when the grade configuration has an exam and the period does not', function () {
        $escenario = escenarioCalificarCierres(['porcentaje_examen' => '0.00']);
        configurarCierrePorGrado($escenario, '30.00', '0.00');
        guardarContextoCierre($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.examenes.index'))
            ->assertSee('Nombre del Examen')
            ->assertDontSee('Este ciclo no tiene examen configurado.');
    });

    it('shows the exam and project links to teachers', function () {
        $this->seed(NavigationSeeder::class);
        $escenario = escenarioCalificarCierres();

        $this->actingAs($escenario['docente'])
            ->withSession([ActiveRole::SESSION_KEY => Role::Docente->value])
            ->get(route('dashboard'))
            ->assertSee('Exámenes')
            ->assertSee('Proyectos')
            ->assertSee('Examen final')
            ->assertSee('Proyecto final')
            ->assertSee(route('Docente.examenes.index'), false)
            ->assertSee(route('Docente.proyectos.index'), false)
            ->assertSee(route('Docente.examenes-finales.index'), false)
            ->assertSee(route('Docente.proyectos-finales.index'), false)
            ->assertDontSee('Nuevo Examen')
            ->assertDontSee('Editar Examen')
            ->assertDontSee('Nuevo Proyecto')
            ->assertDontSee('Editar Proyecto');
    });

    it('loads the saved exam when the teacher opens the same cycle', function () {
        $escenario = escenarioCalificarCierres();
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Herrera');
        matricularAlumnoCierre($escenario['aula'], $alumno);
        $actividad = EstablecimientoCierreActividad::factory()->create([
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'tipo' => TipoCierreEvaluacion::Examen,
            'nombre' => 'Examen primer quimestre',
        ]);
        $actividad->calificaciones()->create([
            'alumno_id' => $alumno->id,
            'nota' => '8.50',
        ]);
        guardarContextoCierre($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.examenes.index'))
            ->assertSee('Examen primer quimestre')
            ->assertSee('value="8.50"', false);
    });
});

describe('store', function () {
    it('stores the classroom context without a partial', function () {
        $escenario = escenarioCalificarCierres();

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.examenes.index'))
            ->post(route('Docente.examenes.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            ])
            ->assertRedirect(route('Docente.examenes.index'));
    });

    it('returns 404 when the classroom belongs to another teacher', function () {
        $escenario = escenarioCalificarCierres();
        $ajeno = escenarioCalificarCierres(['grado' => '9no Básica']);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.examenes.store'), [
                'establecimiento_aula_id' => $ajeno['aula']->id,
                'establecimiento_asignatura_id' => $ajeno['malla']->id,
                'establecimiento_periodo_ciclo_id' => $ajeno['ciclo']->id,
            ])
            ->assertNotFound();
    });
});

describe('storeActividad', function () {
    it('rejects an exam while the period is not open', function () {
        $escenario = escenarioCalificarCierres();
        $escenario['periodo']->update(['abierto_en' => null]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Vargas');
        matricularAlumnoCierre($escenario['aula'], $alumno);

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.examenes.index'))
            ->post(route('Docente.examenes.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'nombre' => 'Examen primer quimestre',
                'notas' => [
                    $alumno->id => '8.50',
                ],
            ])
            ->assertRedirect(route('Docente.examenes.index'))
            ->assertSessionHasErrors([
                'nombre' => 'Apertura el periodo para ingresar calificaciones.',
            ]);

        $this->assertDatabaseCount('establecimiento_cierre_actividades', 0);
    });

    it('creates an exam for the selected classroom and cycle', function () {
        $escenario = escenarioCalificarCierres();
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Vargas');
        matricularAlumnoCierre($escenario['aula'], $alumno);

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.examenes.index'))
            ->post(route('Docente.examenes.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'nombre' => 'Examen primer quimestre',
                'notas' => [
                    $alumno->id => '8.50',
                ],
            ])
            ->assertRedirect(route('Docente.examenes.index'))
            ->assertSessionHas('status', 'calificaciones-grabadas');

        $this->assertDatabaseHas('establecimiento_cierre_actividades', [
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'tipo' => TipoCierreEvaluacion::Examen->value,
            'nombre' => 'Examen primer quimestre',
        ]);
        $this->assertDatabaseHas('establecimiento_cierre_actividad_calificaciones', [
            'alumno_id' => $alumno->id,
            'nota' => '8.50',
        ]);
    });

    it('does not leave a partial exam when saving grades fails', function () {
        $escenario = escenarioCalificarCierres();
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Vargas');
        matricularAlumnoCierre($escenario['aula'], $alumno);

        Exceptions::fake();
        EstablecimientoCierreActividadCalificacion::creating(function (): void {
            throw new RuntimeException('falla de grabado');
        });

        try {
            $this->actingAs($escenario['docente'])
                ->from(route('Docente.examenes.index'))
                ->post(route('Docente.examenes.actividades.store'), [
                    'establecimiento_aula_id' => $escenario['aula']->id,
                    'establecimiento_asignatura_id' => $escenario['malla']->id,
                    'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                    'nombre' => 'Examen primer quimestre',
                    'notas' => [
                        $alumno->id => '8.50',
                    ],
                ])
                ->assertRedirect(route('Docente.examenes.index'))
                ->assertSessionHas('error', 'No se pudo grabar. No se guardó ningún cambio.');

            Exceptions::assertReported(RuntimeException::class);
            $this->assertDatabaseCount('establecimiento_cierre_actividades', 0);
            $this->assertDatabaseCount('establecimiento_cierre_actividad_calificaciones', 0);
        } finally {
            EstablecimientoCierreActividadCalificacion::flushEventListeners();
        }
    });

    it('rejects a project when the cycle does not have one configured', function () {
        $escenario = escenarioCalificarCierres();

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.proyectos.index'))
            ->post(route('Docente.proyectos.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'nombre' => 'Proyecto de feria',
            ])
            ->assertRedirect(route('Docente.proyectos.index'))
            ->assertSessionHasErrors(['nombre' => 'Este ciclo no tiene Proyecto configurado.']);

        $this->assertDatabaseCount('establecimiento_cierre_actividades', 0);
    });

    it('creates a project when the cycle has a project percentage', function () {
        $escenario = escenarioCalificarCierres(['porcentaje_proyecto' => '20.00']);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.proyectos.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'nombre' => 'Proyecto de feria',
            ])
            ->assertRedirect(route('Docente.proyectos.index'))
            ->assertSessionHas('status', 'calificaciones-grabadas');

        $this->assertDatabaseHas('establecimiento_cierre_actividades', [
            'tipo' => TipoCierreEvaluacion::Proyecto->value,
            'nombre' => 'Proyecto de feria',
        ]);
    });

    it('creates a project when the grade configuration has one and the period does not', function () {
        $escenario = escenarioCalificarCierres();
        configurarCierrePorGrado($escenario, '30.00', '20.00');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.proyectos.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'nombre' => 'Proyecto de feria',
            ])
            ->assertRedirect(route('Docente.proyectos.index'))
            ->assertSessionHas('status', 'calificaciones-grabadas');

        $this->assertDatabaseHas('establecimiento_cierre_actividades', [
            'tipo' => TipoCierreEvaluacion::Proyecto->value,
            'nombre' => 'Proyecto de feria',
        ]);
    });

    it('creates a project when the subject configuration has one and the period does not', function () {
        $escenario = escenarioCalificarCierres();
        configurarCierrePorAsignatura($escenario, '30.00', '20.00');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.proyectos.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'nombre' => 'Proyecto de feria',
            ])
            ->assertRedirect(route('Docente.proyectos.index'))
            ->assertSessionHas('status', 'calificaciones-grabadas');

        $this->assertDatabaseHas('establecimiento_cierre_actividades', [
            'tipo' => TipoCierreEvaluacion::Proyecto->value,
            'nombre' => 'Proyecto de feria',
        ]);
    });

    it('rejects a project when the subject configuration disables it even if the grade has one', function () {
        $escenario = escenarioCalificarCierres();
        configurarCierrePorGrado($escenario, '30.00', '20.00');
        configurarCierrePorAsignatura($escenario, '70.00', '0.00');

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.proyectos.index'))
            ->post(route('Docente.proyectos.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'nombre' => 'Proyecto de feria',
            ])
            ->assertRedirect(route('Docente.proyectos.index'))
            ->assertSessionHasErrors(['nombre' => 'Este ciclo no tiene Proyecto configurado.']);

        $this->assertDatabaseCount('establecimiento_cierre_actividades', 0);
    });

    it('rejects an exam when the grade configuration disables it even if the period has one', function () {
        $escenario = escenarioCalificarCierres();
        configurarCierrePorGrado($escenario, '0.00', '20.00');

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.examenes.index'))
            ->post(route('Docente.examenes.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'nombre' => 'Examen primer quimestre',
            ])
            ->assertRedirect(route('Docente.examenes.index'))
            ->assertSessionHasErrors(['nombre' => 'Este ciclo no tiene Examen configurado.']);

        $this->assertDatabaseCount('establecimiento_cierre_actividades', 0);
    });

    it('redirects back when the exam name is missing', function () {
        $escenario = escenarioCalificarCierres();

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.examenes.index'))
            ->post(route('Docente.examenes.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'nombre' => '',
            ])
            ->assertRedirect(route('Docente.examenes.index'))
            ->assertSessionHasErrors(['nombre' => 'Escribe el nombre de la actividad.']);
    });
});

describe('updateActividad', function () {
    it('updates the existing exam of the cycle instead of creating another', function () {
        $escenario = escenarioCalificarCierres();
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Herrera');
        matricularAlumnoCierre($escenario['aula'], $alumno);
        $actividad = EstablecimientoCierreActividad::factory()->create([
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'tipo' => TipoCierreEvaluacion::Examen,
            'nombre' => 'Examen borrador',
        ]);

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.examenes.index'))
            ->post(route('Docente.examenes.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'nombre' => 'Examen final del quimestre',
                'notas' => [
                    $alumno->id => '9.00',
                ],
            ])
            ->assertRedirect(route('Docente.examenes.index'))
            ->assertSessionHas('status', 'calificaciones-grabadas');

        $this->assertDatabaseCount('establecimiento_cierre_actividades', 1);
        $this->assertDatabaseHas('establecimiento_cierre_actividades', [
            'id' => $actividad->id,
            'nombre' => 'Examen final del quimestre',
        ]);
        $this->assertDatabaseHas('establecimiento_cierre_actividad_calificaciones', [
            'establecimiento_cierre_actividad_id' => $actividad->id,
            'alumno_id' => $alumno->id,
            'nota' => '9.00',
        ]);
    });

    it('does not load a project when the teacher opens the exam screen', function () {
        $escenario = escenarioCalificarCierres(['porcentaje_proyecto' => '20.00']);
        EstablecimientoCierreActividad::factory()->create([
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'tipo' => TipoCierreEvaluacion::Proyecto,
            'nombre' => 'Proyecto de feria',
        ]);
        guardarContextoCierre($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.examenes.index'))
            ->assertDontSee('Proyecto de feria')
            ->assertDontSee('value="Proyecto de feria"', false);
    });
});

describe('cierres finales', function () {
    it('creates a final exam when the period has one configured', function () {
        $escenario = escenarioCalificarCierres();
        $escenario['periodo']->update([
            'porcentaje_examen_final' => '20.00',
            'porcentaje_proyecto_final' => '0.00',
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Castro');
        matricularAlumnoCierre($escenario['aula'], $alumno);

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.examenes-finales.index'))
            ->post(route('Docente.examenes-finales.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'nombre' => 'Examen final del periodo',
                'notas' => [
                    $alumno->id => '8.00',
                ],
            ])
            ->assertRedirect(route('Docente.examenes-finales.index'))
            ->assertSessionHas('status', 'calificaciones-grabadas');

        $this->assertDatabaseHas('establecimiento_cierre_actividades', [
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'tipo' => TipoCierreEvaluacion::ExamenFinal->value,
            'nombre' => 'Examen final del periodo',
        ]);
        $this->assertDatabaseHas('establecimiento_cierre_actividad_calificaciones', [
            'alumno_id' => $alumno->id,
            'nota' => '8.00',
        ]);
    });

    it('creates a final exam when the classroom grade has one and the period does not', function () {
        $escenario = escenarioCalificarCierres();
        configurarCierrePorGrado($escenario, '30.00', '0.00', '20.00', '0.00');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.examenes-finales.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'nombre' => 'Examen final del grado',
            ])
            ->assertRedirect(route('Docente.examenes-finales.index'))
            ->assertSessionHas('status', 'calificaciones-grabadas');

        $this->assertDatabaseHas('establecimiento_cierre_actividades', [
            'tipo' => TipoCierreEvaluacion::ExamenFinal->value,
            'nombre' => 'Examen final del grado',
        ]);
    });

    it('rejects a final exam when the classroom grade disables it even if the period has one', function () {
        $escenario = escenarioCalificarCierres();
        $escenario['periodo']->update([
            'porcentaje_examen_final' => '20.00',
            'porcentaje_proyecto_final' => '0.00',
        ]);
        configurarCierrePorGrado($escenario, '50.00', '0.00', '0.00', '0.00');

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.examenes-finales.index'))
            ->post(route('Docente.examenes-finales.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'nombre' => 'Examen final del periodo',
            ])
            ->assertRedirect(route('Docente.examenes-finales.index'))
            ->assertSessionHasErrors(['nombre' => 'Esta aula no tiene Examen final configurado.']);

        $this->assertDatabaseCount('establecimiento_cierre_actividades', 0);
    });

    it('creates a final project when the period has one configured', function () {
        $escenario = escenarioCalificarCierres();
        $escenario['periodo']->update([
            'porcentaje_examen_final' => '0.00',
            'porcentaje_proyecto_final' => '15.00',
        ]);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.proyectos-finales.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'nombre' => 'Proyecto final institucional',
            ])
            ->assertRedirect(route('Docente.proyectos-finales.index'))
            ->assertSessionHas('status', 'calificaciones-grabadas');

        $this->assertDatabaseHas('establecimiento_cierre_actividades', [
            'tipo' => TipoCierreEvaluacion::ProyectoFinal->value,
            'nombre' => 'Proyecto final institucional',
        ]);
    });

    it('updates the existing final exam of the classroom instead of creating another', function () {
        $escenario = escenarioCalificarCierres();
        $escenario['periodo']->update([
            'porcentaje_examen_final' => '20.00',
            'porcentaje_proyecto_final' => '0.00',
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Mora');
        matricularAlumnoCierre($escenario['aula'], $alumno);
        $actividad = EstablecimientoCierreActividad::factory()->create([
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'tipo' => TipoCierreEvaluacion::ExamenFinal,
            'nombre' => 'Examen final borrador',
        ]);

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.examenes-finales.index'))
            ->post(route('Docente.examenes-finales.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'nombre' => 'Examen final publicado',
                'notas' => [
                    $alumno->id => '9.25',
                ],
            ])
            ->assertRedirect(route('Docente.examenes-finales.index'))
            ->assertSessionHas('status', 'calificaciones-grabadas');

        $this->assertDatabaseCount('establecimiento_cierre_actividades', 1);
        $this->assertDatabaseHas('establecimiento_cierre_actividades', [
            'id' => $actividad->id,
            'nombre' => 'Examen final publicado',
        ]);
        $this->assertDatabaseHas('establecimiento_cierre_actividad_calificaciones', [
            'establecimiento_cierre_actividad_id' => $actividad->id,
            'alumno_id' => $alumno->id,
            'nota' => '9.25',
        ]);
    });

    it('opens the final exam form when the classroom grade has one configured', function () {
        $escenario = escenarioCalificarCierres();
        configurarCierrePorGrado($escenario, '30.00', '0.00', '20.00', '0.00');
        guardarContextoCierre($escenario, 'Docente.examenes-finales.store');

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.examenes-finales.index'))
            ->assertSee('Nombre del Examen final')
            ->assertDontSee('Esta aula no tiene Examen final configurado.');
    });
});

describe('mejoras de calificaciones', function () {
    it('records a direct improvement under the 2026 rules and uses it in the gradebook', function () {
        $escenario = escenarioCalificarCierres();
        $escenario['establecimiento']->update(['regimen' => 'Sierra']);
        $escenario['periodo']->update([
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2027-06-30',
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Vega');
        matricularAlumnoCierre($escenario['aula'], $alumno);
        $actividad = EstablecimientoCierreActividad::factory()->create([
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'tipo' => TipoCierreEvaluacion::Examen,
        ]);
        $calificacion = EstablecimientoCierreActividadCalificacion::factory()->create([
            'establecimiento_cierre_actividad_id' => $actividad->id,
            'alumno_id' => $alumno->id,
            'nota' => '8.00',
            'updated_at' => now()->subDay(),
        ]);
        guardarContextoCierre($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.mejora-calificaciones.index'))
            ->assertOk()
            ->assertSee('Mejoras de calificaciones')
            ->assertSee('8.00')
            ->assertSee('Mejora directa');

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.mejora-calificaciones.store'), [
                'calificacion_id' => $calificacion->id,
                'nota_adicional' => '10.00',
                'fecha_notificacion' => today()->toDateString(),
                'fecha_solicitud' => today()->toDateString(),
                'fecha_evaluacion' => today()->toDateString(),
                'referencia_solicitud' => 'SOL-2026-001',
            ])
            ->assertRedirect(route('Docente.mejora-calificaciones.index'))
            ->assertSessionHas('status', 'mejora-calificacion-registrada');

        $this->assertDatabaseHas('establecimiento_mejoras_calificacion', [
            'establecimiento_cierre_actividad_calificacion_id' => $calificacion->id,
            'tipo_mejora' => 'directa',
            'nota_inicial' => '8.00',
            'nota_adicional' => '10.00',
            'nota_final' => '9.00',
            'normativa_aplicada' => '2026',
        ]);

        $libreta = app(LibretaAula::class)->paraAula($escenario['aula']);
        expect($libreta['materias'][0]['filas'][0]['celdas']['examen-'.$escenario['ciclo']->id])->toBe('9.00');
    });

    it('averages the initial, reinforcement, and additional grades under the 2024 rules', function () {
        $escenario = escenarioCalificarCierres();
        $escenario['establecimiento']->update(['regimen' => 'Costa']);
        $escenario['periodo']->update([
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2027-02-25',
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Mora');
        matricularAlumnoCierre($escenario['aula'], $alumno);
        $actividad = EstablecimientoCierreActividad::factory()->create([
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'tipo' => TipoCierreEvaluacion::Examen,
        ]);
        $calificacion = EstablecimientoCierreActividadCalificacion::factory()->create([
            'establecimiento_cierre_actividad_id' => $actividad->id,
            'alumno_id' => $alumno->id,
            'nota' => '6.00',
            'updated_at' => now()->subDay(),
        ]);
        guardarContextoCierre($escenario);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.mejora-calificaciones.store'), [
                'calificacion_id' => $calificacion->id,
                'nota_refuerzo' => '8.00',
                'nota_adicional' => '10.00',
                'fecha_notificacion' => today()->toDateString(),
                'fecha_solicitud' => today()->toDateString(),
                'fecha_evaluacion' => today()->toDateString(),
                'referencia_solicitud' => 'SOL-2026-002',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('establecimiento_mejoras_calificacion', [
            'establecimiento_cierre_actividad_calificacion_id' => $calificacion->id,
            'tipo_mejora' => 'refuerzo',
            'nota_final' => '8.00',
            'normativa_aplicada' => '2024',
        ]);
    });

    it('rejects a grade of exactly seven because it is outside the regulatory ranges', function () {
        $escenario = escenarioCalificarCierres();
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Paz');
        matricularAlumnoCierre($escenario['aula'], $alumno);
        $actividad = EstablecimientoCierreActividad::factory()->create([
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'tipo' => TipoCierreEvaluacion::Examen,
        ]);
        $calificacion = EstablecimientoCierreActividadCalificacion::factory()->create([
            'establecimiento_cierre_actividad_id' => $actividad->id,
            'alumno_id' => $alumno->id,
            'nota' => '7.00',
        ]);
        guardarContextoCierre($escenario);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.mejora-calificaciones.store'), [
                'calificacion_id' => $calificacion->id,
                'nota_adicional' => '10.00',
                'fecha_notificacion' => today()->toDateString(),
                'fecha_solicitud' => today()->toDateString(),
                'fecha_evaluacion' => today()->toDateString(),
                'referencia_solicitud' => 'SOL-2026-003',
            ])
            ->assertSessionHasErrors('calificacion_id');

        $this->assertDatabaseCount('establecimiento_mejoras_calificacion', 0);
    });

    it('enforces the 2024 request and reinforcement deadlines', function () {
        $escenario = escenarioCalificarCierres();
        $escenario['establecimiento']->update(['regimen' => 'Costa']);
        $escenario['periodo']->update([
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2027-02-25',
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Luna');
        matricularAlumnoCierre($escenario['aula'], $alumno);
        $actividad = EstablecimientoCierreActividad::factory()->create([
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'tipo' => TipoCierreEvaluacion::Examen,
        ]);
        $calificacion = EstablecimientoCierreActividadCalificacion::factory()->create([
            'establecimiento_cierre_actividad_id' => $actividad->id,
            'alumno_id' => $alumno->id,
            'nota' => '6.00',
        ]);
        guardarContextoCierre($escenario);
        $solicitud = [
            'calificacion_id' => $calificacion->id,
            'nota_refuerzo' => '8.00',
            'nota_adicional' => '10.00',
            'fecha_notificacion' => today()->subDays(10)->toDateString(),
            'fecha_solicitud' => today()->toDateString(),
            'fecha_evaluacion' => today()->toDateString(),
            'referencia_solicitud' => 'SOL-2026-004',
        ];

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.mejora-calificaciones.store'), $solicitud)
            ->assertSessionHasErrors('fecha_solicitud');

        $solicitud['fecha_solicitud'] = today()->subDays(9)->toDateString();

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.mejora-calificaciones.store'), $solicitud)
            ->assertSessionHasErrors('fecha_evaluacion');

        $this->assertDatabaseCount('establecimiento_mejoras_calificacion', 0);
    });
});
