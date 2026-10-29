<?php

use App\Enums\EsquemaCiclo;
use App\Enums\Role;
use App\Enums\TipoAsignatura;
use App\Enums\TipoCalificacion;
use App\Enums\TipoContenidoTarea;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\EstablecimientoTarea;
use App\Models\EstablecimientoTareaEntrega;
use App\Models\EstablecimientoTareaEntregaArchivo;
use App\Models\Persona;
use App\Models\Sys_Asignatura;
use App\Models\User;
use Database\Seeders\NavigationSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @return array{
 *     establecimiento: Establecimiento,
 *     docente: User,
 *     empleado: Empleado,
 *     aula: EstablecimientoAula,
 *     malla: EstablecimientoAsignatura,
 *     ciclo: EstablecimientoPeriodoCiclo,
 *     alumno: Alumno,
 *     companero: Alumno,
 *     cuentaAlumno: User,
 *     cuentaCompanero: User
 * }
 */
function escenarioTareasAlumno(array $atributos = []): array
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
    $alumno = alumnoDelEstablecimiento($establecimiento, 'Herrera');
    EstablecimientoMatricula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_aula_id' => $aula->id,
        'alumno_id' => $alumno->id,
    ]);
    $companero = alumnoDelEstablecimiento($establecimiento, 'Castro');
    EstablecimientoMatricula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_aula_id' => $aula->id,
        'alumno_id' => $companero->id,
    ]);
    $cuentaAlumno = cuentaAlumnoDe($alumno, $establecimiento);
    $cuentaCompanero = cuentaAlumnoDe($companero, $establecimiento);

    return compact(
        'establecimiento',
        'docente',
        'empleado',
        'aula',
        'malla',
        'ciclo',
        'alumno',
        'companero',
        'cuentaAlumno',
        'cuentaCompanero',
    );
}

/**
 * @param  list<int>  $alumnoIds
 * @param  array<string, mixed>  $atributos
 */
function registrarTareaAlumno(array $escenario, array $alumnoIds, array $atributos = []): EstablecimientoTarea
{
    $tarea = EstablecimientoTarea::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'empleado_id' => $escenario['empleado']->id,
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'parcial_numero' => 1,
        'fecha_inicio' => '2026-10-01',
        'fecha_fin' => '2026-10-31',
        ...$atributos,
    ]);

    foreach ($alumnoIds as $alumnoId) {
        $tarea->destinatarios()->create(['alumno_id' => $alumnoId]);
    }

    return $tarea;
}

describe('index', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('alumno.tareas.index'))
            ->assertRedirect(route('login'));
    });

    it('forbids a teacher from opening student homework', function () {
        $escenario = escenarioTareasAlumno();

        $this->actingAs($escenario['docente'])
            ->get(route('alumno.tareas.index'))
            ->assertForbidden();
    });

    it('lists only homework assigned to the student', function () {
        $escenario = escenarioTareasAlumno();
        registrarTareaAlumno($escenario, [$escenario['alumno']->id], ['nombre' => 'Mi ensayo']);
        registrarTareaAlumno($escenario, [$escenario['companero']->id], ['nombre' => 'Tarea del compañero']);

        $this->actingAs($escenario['cuentaAlumno'])
            ->get(route('alumno.tareas.index', ['mes' => '2026-10']))
            ->assertSee('Mi ensayo')
            ->assertDontSee('Tarea del compañero');
    });

    it('places assigned homework on the sent day of a large calendar', function () {
        $this->travelTo('2026-10-10 12:00:00');
        $escenario = escenarioTareasAlumno();
        $tarea = registrarTareaAlumno($escenario, [$escenario['alumno']->id], ['nombre' => 'Mi ensayo']);

        $this->actingAs($escenario['cuentaAlumno'])
            ->get(route('alumno.tareas.index', ['mes' => '2026-10']))
            ->assertSee('Octubre 2026')
            ->assertSee('Lun')
            ->assertSee('Mi ensayo')
            ->assertSee("open-modal', 'tarea-alumno-{$tarea->id}'", false)
            ->assertSee('Enviada el 01/10/2026');
    });

    it('opens the homework modal with a downloadable teacher file', function () {
        Storage::fake('local');
        $this->travelTo('2026-10-10 12:00:00');
        $escenario = escenarioTareasAlumno();
        Storage::disk('local')->put('tareas/contenido/guia.pdf', 'contenido-pdf');
        $tarea = registrarTareaAlumno($escenario, [$escenario['alumno']->id], [
            'nombre' => 'Lee la guía',
            'descripcion' => '<p>Resuelve los ejercicios.</p>',
            'contenido_tipo' => TipoContenidoTarea::Pdf,
            'contenido_texto' => null,
            'contenido_path' => 'tareas/contenido/guia.pdf',
            'contenido_nombre' => 'guia.pdf',
        ]);

        $this->actingAs($escenario['cuentaAlumno'])
            ->get(route('alumno.tareas.index', ['mes' => '2026-10', 'tarea' => $tarea->id]))
            ->assertSee('Lee la guía')
            ->assertSee('Resuelve los ejercicios.')
            ->assertSee('Descargar guia.pdf')
            ->assertSee('Texto de la entrega')
            ->assertSee(route('alumno.tareas.archivo', $tarea), false);
    });

    it('shows homework in the student menu', function () {
        $this->seed(NavigationSeeder::class);
        $escenario = escenarioTareasAlumno();

        $this->actingAs($escenario['cuentaAlumno'])
            ->get(route('dashboard'))
            ->assertSee('Tareas')
            ->assertSee(route('alumno.tareas.index'), false);
    });
});

describe('show', function () {
    it('returns 404 when the student is not a recipient', function () {
        $escenario = escenarioTareasAlumno();
        $tarea = registrarTareaAlumno($escenario, [$escenario['companero']->id]);

        $this->actingAs($escenario['cuentaAlumno'])
            ->get(route('alumno.tareas.show', $tarea))
            ->assertNotFound();
    });

    it('shows the assigned homework and the grade', function () {
        $escenario = escenarioTareasAlumno();
        $tarea = registrarTareaAlumno($escenario, [$escenario['alumno']->id], [
            'descripcion' => '<p>Lee el capítulo.</p>',
        ]);
        $tarea->destinatarios()->where('alumno_id', $escenario['alumno']->id)->update([
            'nota' => '9.00',
            'observacion' => 'Excelente',
            'calificada_at' => now(),
        ]);

        $this->actingAs($escenario['cuentaAlumno'])
            ->get(route('alumno.tareas.show', $tarea))
            ->assertSee('Ensayo de la semana')
            ->assertSee('Lee el capítulo.')
            ->assertSee('Calificación:')
            ->assertSee('9.00');
    });

    it('shows the insumo grade after the teacher saved the homework as an activity', function () {
        $escenario = escenarioTareasAlumno();
        $tarea = registrarTareaAlumno($escenario, [$escenario['alumno']->id]);
        $actividad = EstablecimientoInsumoActividad::factory()->create([
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'parcial_numero' => 1,
            'empleado_id' => $escenario['empleado']->id,
            'nombre' => $tarea->nombre,
        ]);
        $actividad->calificaciones()->create([
            'alumno_id' => $escenario['alumno']->id,
            'nota' => '9.00',
        ]);
        $tarea->update(['establecimiento_insumo_actividad_id' => $actividad->id]);

        $this->actingAs($escenario['cuentaAlumno'])
            ->get(route('alumno.tareas.show', $tarea))
            ->assertSee('Calificación:')
            ->assertSee('9.00');
    });
});

describe('entregar', function () {
    it('stores a message and a file from the student', function () {
        Storage::fake('local');
        $this->travelTo('2026-10-10 12:00:00');
        $escenario = escenarioTareasAlumno();
        $tarea = registrarTareaAlumno($escenario, [$escenario['alumno']->id]);

        $this->actingAs($escenario['cuentaAlumno'])
            ->post(route('alumno.tareas.entregar', $tarea), [
                'mensaje' => 'Adjunto el ensayo.',
                'archivos' => [UploadedFile::fake()->create('ensayo.pdf', 80, 'application/pdf')],
            ])
            ->assertRedirect(route('alumno.tareas.index', [
                'mes' => '2026-10',
                'tarea' => $tarea->id,
            ]));

        $entrega = EstablecimientoTareaEntrega::query()->firstOrFail();

        expect($entrega->mensaje)->toBe('Adjunto el ensayo.')
            ->and($entrega->alumno_id)->toBe($escenario['alumno']->id);
        $this->assertDatabaseHas('establecimiento_tarea_entrega_archivos', [
            'establecimiento_tarea_entrega_id' => $entrega->id,
            'nombre_original' => 'ensayo.pdf',
        ]);
        Storage::disk('local')->assertExists($entrega->archivos()->firstOrFail()->path);
    });

    it('stores a spreadsheet submitted by the student', function () {
        Storage::fake('local');
        $this->travelTo('2026-10-10 12:00:00');
        $escenario = escenarioTareasAlumno();
        $tarea = registrarTareaAlumno($escenario, [$escenario['alumno']->id]);

        $this->actingAs($escenario['cuentaAlumno'])
            ->post(route('alumno.tareas.entregar', $tarea), [
                'mensaje' => '',
                'archivos' => [UploadedFile::fake()->create(
                    'tabla.xlsx',
                    40,
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                )],
            ])
            ->assertRedirect(route('alumno.tareas.index', [
                'mes' => '2026-10',
                'tarea' => $tarea->id,
            ]));

        $this->assertDatabaseHas('establecimiento_tarea_entrega_archivos', [
            'nombre_original' => 'tabla.xlsx',
        ]);
    });

    it('rejects an empty submission', function () {
        $this->travelTo('2026-10-10 12:00:00');
        $escenario = escenarioTareasAlumno();
        $tarea = registrarTareaAlumno($escenario, [$escenario['alumno']->id]);

        $this->actingAs($escenario['cuentaAlumno'])
            ->from(route('alumno.tareas.index', ['mes' => '2026-10']))
            ->post(route('alumno.tareas.entregar', $tarea), [
                'mensaje' => '',
            ])
            ->assertRedirect(route('alumno.tareas.index', [
                'mes' => '2026-10',
                'tarea' => $tarea->id,
            ]))
            ->assertSessionHasErrors(['archivos' => 'Escribe un texto o adjunta al menos un documento.']);

        $this->assertDatabaseCount('establecimiento_tarea_entregas', 0);
    });

    it('returns 404 when the due date has passed', function () {
        $this->travelTo('2026-11-02 12:00:00');
        $escenario = escenarioTareasAlumno();
        $tarea = registrarTareaAlumno($escenario, [$escenario['alumno']->id], [
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-31',
        ]);

        $this->actingAs($escenario['cuentaAlumno'])
            ->post(route('alumno.tareas.entregar', $tarea), [
                'mensaje' => 'Tarde',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('establecimiento_tarea_entregas', 0);
    });

    it('returns 404 when a classmate submits for another student', function () {
        $this->travelTo('2026-10-10 12:00:00');
        $escenario = escenarioTareasAlumno();
        $tarea = registrarTareaAlumno($escenario, [$escenario['alumno']->id]);

        $this->actingAs($escenario['cuentaCompanero'])
            ->post(route('alumno.tareas.entregar', $tarea), [
                'mensaje' => 'No es mía',
            ])
            ->assertNotFound();
    });
});

describe('archivo', function () {
    it('downloads the homework file for the assigned student', function () {
        Storage::fake('local');
        $escenario = escenarioTareasAlumno();
        Storage::disk('local')->put('tareas/contenido/guia.pdf', 'contenido-pdf');
        $tarea = registrarTareaAlumno($escenario, [$escenario['alumno']->id], [
            'contenido_tipo' => TipoContenidoTarea::Pdf,
            'contenido_texto' => null,
            'contenido_path' => 'tareas/contenido/guia.pdf',
            'contenido_nombre' => 'guia.pdf',
        ]);

        $this->actingAs($escenario['cuentaAlumno'])
            ->get(route('alumno.tareas.archivo', $tarea))
            ->assertDownload('guia.pdf');
    });

    it('returns 404 when a classmate downloads another student submission', function () {
        Storage::fake('local');
        $escenario = escenarioTareasAlumno();
        $tarea = registrarTareaAlumno($escenario, [$escenario['alumno']->id, $escenario['companero']->id]);
        Storage::disk('local')->put('tareas/entregas/ensayo.pdf', 'ensayo');
        $entrega = EstablecimientoTareaEntrega::factory()->create([
            'establecimiento_tarea_id' => $tarea->id,
            'alumno_id' => $escenario['alumno']->id,
        ]);
        $archivo = EstablecimientoTareaEntregaArchivo::factory()->create([
            'establecimiento_tarea_entrega_id' => $entrega->id,
            'path' => 'tareas/entregas/ensayo.pdf',
            'nombre_original' => 'ensayo.pdf',
        ]);

        $this->actingAs($escenario['cuentaCompanero'])
            ->get(route('alumno.tareas.archivo-entrega', [$tarea, $archivo]))
            ->assertNotFound();
    });
});
