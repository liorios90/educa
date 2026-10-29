<?php

use App\Auth\ActiveRole;
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
use App\Models\EstablecimientoInsumoActividadCalificacion;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\EstablecimientoTarea;
use App\Models\EstablecimientoTareaDestinatario;
use App\Models\EstablecimientoTareaEntrega;
use App\Models\EstablecimientoTareaEntregaArchivo;
use App\Models\NavigationItem;
use App\Models\Persona;
use App\Models\Sys_Asignatura;
use App\Models\TipoInsumo;
use App\Models\TipoInsumoDetalle;
use App\Models\User;
use Database\Seeders\NavigationSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
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
 *     cuentaAlumno: User
 * }
 */
function escenarioTareasDocente(array $atributos = []): array
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
    $malla = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $catalogo->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => $atributos['asignatura'] ?? 'Matemática'])->id,
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
        'nombre' => $atributos['ciclo'] ?? 'Primer quimestre',
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
    );
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function payloadTareaDocente(array $escenario, array $overrides = []): array
{
    return [
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'parcial_numero' => 1,
        'nombre' => 'Ensayo de la semana',
        'descripcion' => '<p>Lee el capítulo y responde.</p>',
        'fecha_inicio' => '2026-10-01',
        'fecha_fin' => '2026-10-15',
        'toda_el_aula' => '1',
        'contenido_tipo' => TipoContenidoTarea::Texto->value,
        'contenido_texto' => 'Resuelve los ejercicios 1 a 5.',
        ...$overrides,
    ];
}

function guardarContextoTareas(array $escenario, int $parcial = 1): void
{
    test()->actingAs($escenario['docente'])
        ->post(route('Docente.tareas.contexto'), [
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'parcial_numero' => $parcial,
            'destino' => 'tareas',
        ])
        ->assertRedirect(route('Docente.tareas.index'));
}

/**
 * @param  list<int>  $alumnoIds
 * @param  array<string, mixed>  $atributos
 */
function registrarTareaDocente(array $escenario, array $alumnoIds, array $atributos = []): EstablecimientoTarea
{
    $tarea = EstablecimientoTarea::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'empleado_id' => $escenario['empleado']->id,
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'parcial_numero' => 1,
        ...$atributos,
    ]);

    foreach ($alumnoIds as $alumnoId) {
        $tarea->destinatarios()->create(['alumno_id' => $alumnoId]);
    }

    return $tarea;
}

/**
 * @param  list<array{nombre: string, valor?: string|null, porcentaje?: string|null}>  $detalles
 * @return array{tipo: TipoInsumo, detalles: list<TipoInsumoDetalle>}
 */
function asignarTipoInsumoTareas(array $escenario, string $tipoCalificacion, array $detalles): array
{
    $tipo = tipoInsumoDe($escenario['aula']->periodo, [
        'nombre' => 'Evaluación de aula',
        'tipo_calificacion' => $tipoCalificacion,
    ]);
    $creados = [];

    foreach ($detalles as $detalle) {
        $creados[] = $tipo->detalles()->create([
            'nombre' => $detalle['nombre'],
            'valor' => $detalle['valor'] ?? null,
            'porcentaje' => $detalle['porcentaje'] ?? null,
            'activo' => true,
        ]);
    }

    DB::table('establecimiento_aula_asignatura_tipo_insumos')->insert([
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'tipo_insumo_id' => $tipo->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [
        'tipo' => $tipo,
        'detalles' => $creados,
    ];
}

describe('index', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('Docente.tareas.index'))
            ->assertRedirect(route('login'));
    });

    it('forbids an administrator from opening homework', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('Docente.tareas.index'))
            ->assertForbidden();
    });

    it('renders the classroom selector and an empty list for the saved context', function () {
        $escenario = escenarioTareasDocente();
        guardarContextoTareas($escenario);

        $this->get(route('Docente.tareas.index'))
            ->assertSee('Tareas')
            ->assertSee('Nueva tarea')
            ->assertSee('Aún no has enviado tareas en este contexto.');
    });

    it('lists only homework of the selected classroom cycle and partial', function () {
        $escenario = escenarioTareasDocente();
        registrarTareaDocente($escenario, [$escenario['alumno']->id], ['nombre' => 'Tarea del parcial 1']);
        registrarTareaDocente($escenario, [$escenario['alumno']->id], [
            'nombre' => 'Tarea del parcial 2',
            'parcial_numero' => 2,
        ]);
        guardarContextoTareas($escenario);

        $this->get(route('Docente.tareas.index'))
            ->assertSee('Tarea del parcial 1')
            ->assertDontSee('Tarea del parcial 2');
    });

    it('lets the teacher grade, modify and delete homework from the list', function () {
        $escenario = escenarioTareasDocente();
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id]);
        guardarContextoTareas($escenario);

        $this->get(route('Docente.tareas.index'))
            ->assertSee('Modificar')
            ->assertSee('Eliminar')
            ->assertSee('Calificar')
            ->assertSee(route('Docente.tareas.edit', $tarea), false)
            ->assertSee(route('Docente.tareas.destroy', $tarea), false)
            ->assertSee(route('Docente.tareas.entregas', $tarea), false);
    });

    it('shows homework inside the insumos menu', function () {
        $this->seed(NavigationSeeder::class);
        $escenario = escenarioTareasDocente();
        $insumos = NavigationItem::query()->where('label', 'Insumos')->where('is_group', true)->firstOrFail();

        $this->actingAs($escenario['docente'])
            ->withSession([ActiveRole::SESSION_KEY => Role::Docente->value])
            ->get(route('navigation.hub', $insumos))
            ->assertSee('Tareas')
            ->assertSee(route('Docente.tareas.index'), false)
            ->assertSee('alumnos puntuales');
    });
});

describe('create', function () {
    it('redirects to the list when no classroom context is selected', function () {
        $escenario = escenarioTareasDocente();

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.tareas.create'))
            ->assertRedirect(route('Docente.tareas.index'));
    });

    it('renders the enrolled students of the classroom', function () {
        $escenario = escenarioTareasDocente();
        guardarContextoTareas($escenario);

        $this->get(route('Docente.tareas.create'))
            ->assertSee('Nueva tarea')
            ->assertSee('Herrera Ana')
            ->assertSee('Castro Ana')
            ->assertSee('Toda el aula');
    });
});

describe('edit', function () {
    it('shows the homework name like the homework page', function () {
        $escenario = escenarioTareasDocente();
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id], [
            'nombre' => 'Ensayo de la semana',
        ]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.tareas.edit', $tarea))
            ->assertSee('Ensayo de la semana')
            ->assertDontSee('Editar tarea');
    });

    it('previews the uploaded pdf and keeps the file input to replace it', function () {
        Storage::fake('local');
        $escenario = escenarioTareasDocente();
        Storage::disk('local')->put('tareas/contenido/guia.pdf', 'contenido-pdf');
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id], [
            'contenido_tipo' => TipoContenidoTarea::Pdf,
            'contenido_texto' => null,
            'contenido_path' => 'tareas/contenido/guia.pdf',
            'contenido_nombre' => 'guia.pdf',
        ]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.tareas.edit', $tarea))
            ->assertSee('guia.pdf')
            ->assertSee('<iframe', false)
            ->assertSee(route('Docente.tareas.archivo', $tarea), false)
            ->assertSee('name="contenido_archivo"', false)
            ->assertSee('Sube uno nuevo solo si quieres reemplazarlo.');
    });
});

describe('store', function () {
    it('sends homework to the whole classroom', function () {
        $escenario = escenarioTareasDocente();
        guardarContextoTareas($escenario);

        $this->post(route('Docente.tareas.store'), payloadTareaDocente($escenario))
            ->assertRedirect(route('Docente.tareas.index'));

        $tarea = EstablecimientoTarea::query()->firstOrFail();

        expect($tarea->nombre)->toBe('Ensayo de la semana')
            ->and($tarea->contenido_tipo)->toBe(TipoContenidoTarea::Texto)
            ->and($tarea->destinatarios()->pluck('alumno_id')->sort()->values()->all())
            ->toBe(collect([$escenario['alumno']->id, $escenario['companero']->id])->sort()->values()->all());
    });

    it('sends homework to one student', function () {
        $escenario = escenarioTareasDocente();
        guardarContextoTareas($escenario);

        $this->post(route('Docente.tareas.store'), payloadTareaDocente($escenario, [
            'toda_el_aula' => '0',
            'destinatarios' => [$escenario['alumno']->id],
        ]))
            ->assertRedirect(route('Docente.tareas.index'));

        $this->assertDatabaseHas('establecimiento_tarea_destinatarios', [
            'alumno_id' => $escenario['alumno']->id,
        ]);
        $this->assertDatabaseMissing('establecimiento_tarea_destinatarios', [
            'alumno_id' => $escenario['companero']->id,
        ]);
    });

    it('sends homework to several students', function () {
        $escenario = escenarioTareasDocente();
        guardarContextoTareas($escenario);

        $this->post(route('Docente.tareas.store'), payloadTareaDocente($escenario, [
            'toda_el_aula' => '0',
            'destinatarios' => [$escenario['alumno']->id, $escenario['companero']->id],
        ]))
            ->assertRedirect(route('Docente.tareas.index'));

        expect(EstablecimientoTareaDestinatario::query()->count())->toBe(2);
    });

    it('stores a pdf as private homework content', function () {
        Storage::fake('local');
        $escenario = escenarioTareasDocente();
        guardarContextoTareas($escenario);

        $this->post(route('Docente.tareas.store'), payloadTareaDocente($escenario, [
            'contenido_tipo' => TipoContenidoTarea::Pdf->value,
            'contenido_texto' => null,
            'contenido_archivo' => UploadedFile::fake()->create('guia.pdf', 120, 'application/pdf'),
        ]))
            ->assertRedirect(route('Docente.tareas.index'));

        $tarea = EstablecimientoTarea::query()->firstOrFail();

        expect($tarea->contenido_tipo)->toBe(TipoContenidoTarea::Pdf)
            ->and($tarea->contenido_nombre)->toBe('guia.pdf');
        Storage::disk('local')->assertExists($tarea->contenido_path);
    });

    it('strips scripts from the description', function () {
        $escenario = escenarioTareasDocente();
        guardarContextoTareas($escenario);

        $this->post(route('Docente.tareas.store'), payloadTareaDocente($escenario, [
            'descripcion' => '<p>Lee</p><script>alert(1)</script><strong>ya</strong>',
        ]))
            ->assertRedirect(route('Docente.tareas.index'));

        $tarea = EstablecimientoTarea::query()->firstOrFail();

        expect($tarea->descripcion)
            ->toContain('<p>Lee</p>')
            ->toContain('<strong>ya</strong>')
            ->not->toContain('<script>');
    });

    it('rejects homework without a name', function () {
        $escenario = escenarioTareasDocente();
        guardarContextoTareas($escenario);

        $this->from(route('Docente.tareas.create'))
            ->post(route('Docente.tareas.store'), payloadTareaDocente($escenario, ['nombre' => '']))
            ->assertRedirect(route('Docente.tareas.create'))
            ->assertSessionHasErrors(['nombre' => 'Escribe el nombre de la tarea.']);

        $this->assertDatabaseCount('establecimiento_tareas', 0);
    });

    it('rejects homework without recipients', function () {
        $escenario = escenarioTareasDocente();
        guardarContextoTareas($escenario);

        $this->from(route('Docente.tareas.create'))
            ->post(route('Docente.tareas.store'), payloadTareaDocente($escenario, [
                'toda_el_aula' => '0',
                'destinatarios' => [],
            ]))
            ->assertRedirect(route('Docente.tareas.create'))
            ->assertSessionHasErrors(['destinatarios' => 'Selecciona al menos un alumno.']);
    });

    it('does not assign a student from another classroom', function () {
        $escenario = escenarioTareasDocente();
        $ajeno = escenarioTareasDocente(['grado' => '9no Básica']);
        guardarContextoTareas($escenario);

        $this->from(route('Docente.tareas.create'))
            ->post(route('Docente.tareas.store'), payloadTareaDocente($escenario, [
                'toda_el_aula' => '0',
                'destinatarios' => [$ajeno['alumno']->id],
            ]))
            ->assertRedirect(route('Docente.tareas.create'))
            ->assertSessionHasErrors(['destinatarios' => 'Selecciona al menos un alumno.']);
    });

    it('returns 404 when the classroom is not assigned to the teacher', function () {
        $escenario = escenarioTareasDocente();
        $ajeno = escenarioTareasDocente(['grado' => '9no Básica']);
        guardarContextoTareas($escenario);

        $this->post(route('Docente.tareas.store'), payloadTareaDocente($ajeno))
            ->assertNotFound();
    });
});

describe('show', function () {
    it('returns 404 to another teacher of the same school', function () {
        $escenario = escenarioTareasDocente();
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id]);
        $colega = escenarioTareasDocente(['grado' => '9no Básica', 'paralelo' => 'B']);
        $colega['docente']->update(['establecimiento_id' => $escenario['establecimiento']->id]);

        $this->actingAs($colega['docente'])
            ->get(route('Docente.tareas.show', $tarea))
            ->assertNotFound();
    });

    it('returns 404 to a teacher of another school', function () {
        $escenario = escenarioTareasDocente();
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id]);
        $ajeno = escenarioTareasDocente(['grado' => '9no Básica']);

        $this->actingAs($ajeno['docente'])
            ->get(route('Docente.tareas.show', $tarea))
            ->assertNotFound();
    });

    it('shows the homework without student submissions or grading', function () {
        $escenario = escenarioTareasDocente();
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id]);
        EstablecimientoTareaEntrega::factory()->create([
            'establecimiento_tarea_id' => $tarea->id,
            'alumno_id' => $escenario['alumno']->id,
            'mensaje' => 'Adjunto el ensayo.',
        ]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.tareas.show', $tarea))
            ->assertSee('Ensayo de la semana')
            ->assertDontSee('Entregas de los alumnos')
            ->assertDontSee('Adjunto el ensayo.')
            ->assertDontSee('Calificar')
            ->assertDontSee(route('Docente.tareas.edit', $tarea), false)
            ->assertDontSee('¿Eliminar esta tarea y sus entregas?', false);
    });

    it('previews the assigned pdf on the homework page', function () {
        Storage::fake('local');
        $escenario = escenarioTareasDocente();
        Storage::disk('local')->put('tareas/contenido/guia.pdf', 'contenido-pdf');
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id], [
            'contenido_tipo' => TipoContenidoTarea::Pdf,
            'contenido_texto' => null,
            'contenido_path' => 'tareas/contenido/guia.pdf',
            'contenido_nombre' => 'guia.pdf',
        ]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.tareas.show', $tarea))
            ->assertSee('<iframe', false)
            ->assertSee('min-height: 1100px', false)
            ->assertSee(route('Docente.tareas.archivo', $tarea), false)
            ->assertDontSee('Descargar guia.pdf');
    });

    it('previews the assigned image on the homework page', function () {
        Storage::fake('local');
        $escenario = escenarioTareasDocente();
        Storage::disk('local')->put('tareas/contenido/foto.jpg', 'contenido-imagen');
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id], [
            'contenido_tipo' => TipoContenidoTarea::Imagen,
            'contenido_texto' => null,
            'contenido_path' => 'tareas/contenido/foto.jpg',
            'contenido_nombre' => 'foto.jpg',
        ]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.tareas.show', $tarea))
            ->assertSee('<img', false)
            ->assertSee('min-height: 1100px', false)
            ->assertSee(route('Docente.tareas.archivo', $tarea), false)
            ->assertDontSee('Descargar foto.jpg');
    });

    it('escapes a dangerous student name', function () {
        $escenario = escenarioTareasDocente();
        $escenario['alumno']->persona->update(['apellidos' => "<script>alert('xss')</script>"]);
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.tareas.show', $tarea))
            ->assertDontSee("<script>alert('xss')</script>", false);
    });
});

describe('entregas', function () {
    it('returns 404 to a teacher of another school', function () {
        $escenario = escenarioTareasDocente();
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id]);
        $ajeno = escenarioTareasDocente(['grado' => '9no Básica']);

        $this->actingAs($ajeno['docente'])
            ->get(route('Docente.tareas.entregas', $tarea))
            ->assertNotFound();
    });

    it('shows a student submission and lets the teacher grade it', function () {
        $escenario = escenarioTareasDocente();
        $insumo = asignarTipoInsumoTareas($escenario, 'cuantitativa', [
            ['nombre' => 'Tarea', 'porcentaje' => '50.00'],
        ]);
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id, $escenario['companero']->id]);
        $entrega = EstablecimientoTareaEntrega::factory()->create([
            'establecimiento_tarea_id' => $tarea->id,
            'alumno_id' => $escenario['alumno']->id,
            'mensaje' => 'Adjunto el ensayo.',
        ]);
        EstablecimientoTareaEntregaArchivo::factory()->create([
            'establecimiento_tarea_entrega_id' => $entrega->id,
            'nombre_original' => 'ensayo.pdf',
        ]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.tareas.entregas', $tarea))
            ->assertSee('Entregas de los alumnos')
            ->assertSee('Herrera Ana')
            ->assertSee('Adjunto el ensayo.')
            ->assertSee('ensayo.pdf')
            ->assertSee('<iframe', false)
            ->assertSee('Descargar ensayo.pdf')
            ->assertSee('Elegir un tipo de insumo:')
            ->assertSee('Grabar calificaciones');

        $this->post(route('Docente.tareas.calificar', $tarea), [
            'tipo_insumo_detalle_id' => $insumo['detalles'][0]->id,
            'notas' => [
                $escenario['alumno']->id => '8.5',
                $escenario['companero']->id => '7',
            ],
        ])
            ->assertRedirect(route('Docente.tareas.entregas', $tarea))
            ->assertSessionHas('status', 'tarea-calificada');

        $this->assertDatabaseHas('establecimiento_insumo_actividades', [
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'parcial_numero' => 1,
            'tipo_insumo_detalle_id' => $insumo['detalles'][0]->id,
            'nombre' => $tarea->nombre,
        ]);
        $this->assertDatabaseHas('establecimiento_insumo_actividad_calificaciones', [
            'alumno_id' => $escenario['alumno']->id,
            'nota' => '8.50',
        ]);
        $this->assertDatabaseHas('establecimiento_insumo_actividad_calificaciones', [
            'alumno_id' => $escenario['companero']->id,
            'nota' => '7.00',
        ]);

        $tarea->refresh();
        expect($tarea->estaCalificada())->toBeTrue();

        $this->get(route('Docente.calificar.editar'))
            ->assertSee($tarea->nombre)
            ->assertSee('Tarea');
    });

    it('shows an office document as a download when grading', function () {
        $escenario = escenarioTareasDocente();
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id]);
        $entrega = EstablecimientoTareaEntrega::factory()->create([
            'establecimiento_tarea_id' => $tarea->id,
            'alumno_id' => $escenario['alumno']->id,
            'mensaje' => 'Adjunto la hoja de cálculo.',
        ]);
        EstablecimientoTareaEntregaArchivo::factory()->create([
            'establecimiento_tarea_entrega_id' => $entrega->id,
            'nombre_original' => 'notas.xlsx',
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'path' => 'tareas/entregas/notas.xlsx',
        ]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.tareas.entregas', $tarea))
            ->assertSee('Adjunto la hoja de cálculo.')
            ->assertSee('Descargar notas.xlsx')
            ->assertDontSee('<iframe', false);
    });

    it('rejects a grade above 10', function () {
        $escenario = escenarioTareasDocente();
        $insumo = asignarTipoInsumoTareas($escenario, 'cuantitativa', [
            ['nombre' => 'Tarea', 'porcentaje' => '50.00'],
        ]);
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id]);

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.tareas.entregas', $tarea))
            ->post(route('Docente.tareas.calificar', $tarea), [
                'tipo_insumo_detalle_id' => $insumo['detalles'][0]->id,
                'notas' => [
                    $escenario['alumno']->id => '11',
                ],
            ])
            ->assertRedirect(route('Docente.tareas.entregas', $tarea))
            ->assertSessionHasErrors(['notas.'.$escenario['alumno']->id => 'La nota nacional va de 0 a 10.']);
    });

    it('does not allow grading again from homework after it became an insumo', function () {
        $escenario = escenarioTareasDocente();
        $insumo = asignarTipoInsumoTareas($escenario, 'cuantitativa', [
            ['nombre' => 'Tarea', 'porcentaje' => '50.00'],
        ]);
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id]);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.tareas.calificar', $tarea), [
                'tipo_insumo_detalle_id' => $insumo['detalles'][0]->id,
                'notas' => [
                    $escenario['alumno']->id => '8.50',
                ],
            ])
            ->assertRedirect(route('Docente.tareas.entregas', $tarea));

        $this->from(route('Docente.tareas.entregas', $tarea))
            ->post(route('Docente.tareas.calificar', $tarea), [
                'tipo_insumo_detalle_id' => $insumo['detalles'][0]->id,
                'notas' => [
                    $escenario['alumno']->id => '9',
                ],
            ])
            ->assertRedirect(route('Docente.tareas.entregas', $tarea))
            ->assertSessionHasErrors(['tipo_insumo_detalle_id' => 'Esta tarea ya está calificada. Cámbiala desde Editar insumo.']);

        $this->get(route('Docente.tareas.entregas', $tarea))
            ->assertSee('Esta tarea ya está calificada.')
            ->assertSee('Editarla en insumos')
            ->assertDontSee('Grabar calificaciones');

        expect(EstablecimientoInsumoActividad::query()->count())->toBe(1);
        $this->assertDatabaseHas('establecimiento_insumo_actividad_calificaciones', [
            'alumno_id' => $escenario['alumno']->id,
            'nota' => '8.50',
        ]);
    });

    it('does not leave a partial insumo when grading fails', function () {
        $escenario = escenarioTareasDocente();
        $insumo = asignarTipoInsumoTareas($escenario, 'cuantitativa', [
            ['nombre' => 'Tarea', 'porcentaje' => '50.00'],
        ]);
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id, $escenario['companero']->id]);

        Exceptions::fake();
        EstablecimientoInsumoActividadCalificacion::creating(function (): void {
            throw new RuntimeException('falla de grabado');
        });

        try {
            $this->actingAs($escenario['docente'])
                ->from(route('Docente.tareas.entregas', $tarea))
                ->post(route('Docente.tareas.calificar', $tarea), [
                    'tipo_insumo_detalle_id' => $insumo['detalles'][0]->id,
                    'notas' => [
                        $escenario['alumno']->id => '8.5',
                        $escenario['companero']->id => '7',
                    ],
                ])
                ->assertRedirect(route('Docente.tareas.entregas', $tarea))
                ->assertSessionHas('error', 'No se pudo grabar. No se guardó ningún cambio.');

            Exceptions::assertReported(RuntimeException::class);
            expect($tarea->fresh()->estaCalificada())->toBeFalse();
            $this->assertDatabaseCount('establecimiento_insumo_actividades', 0);
            $this->assertDatabaseCount('establecimiento_insumo_actividad_calificaciones', 0);
        } finally {
            EstablecimientoInsumoActividadCalificacion::flushEventListeners();
        }
    });

    it('returns 404 when the supply type belongs to another subject', function () {
        $escenario = escenarioTareasDocente();
        $ajeno = escenarioTareasDocente(['grado' => '9no Básica']);
        asignarTipoInsumoTareas($escenario, 'cuantitativa', [
            ['nombre' => 'Tarea', 'porcentaje' => '50.00'],
        ]);
        $insumoAjeno = asignarTipoInsumoTareas($ajeno, 'cuantitativa', [
            ['nombre' => 'Deber', 'porcentaje' => '30.00'],
        ]);
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id]);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.tareas.calificar', $tarea), [
                'tipo_insumo_detalle_id' => $insumoAjeno['detalles'][0]->id,
                'notas' => [
                    $escenario['alumno']->id => '8',
                ],
            ])
            ->assertNotFound();
    });

    it('escapes a dangerous student name', function () {
        $escenario = escenarioTareasDocente();
        $escenario['alumno']->persona->update(['apellidos' => "<script>alert('xss')</script>"]);
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.tareas.entregas', $tarea))
            ->assertDontSee("<script>alert('xss')</script>", false);
    });
});

describe('update', function () {
    it('updates the homework name and recipients', function () {
        $escenario = escenarioTareasDocente();
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id, $escenario['companero']->id]);

        $this->actingAs($escenario['docente'])
            ->put(route('Docente.tareas.update', $tarea), payloadTareaDocente($escenario, [
                'nombre' => 'Ensayo corregido',
                'toda_el_aula' => '0',
                'destinatarios' => [$escenario['alumno']->id],
            ]))
            ->assertRedirect(route('Docente.tareas.show', $tarea));

        expect($tarea->refresh()->nombre)->toBe('Ensayo corregido');
        $this->assertDatabaseHas('establecimiento_tarea_destinatarios', [
            'establecimiento_tarea_id' => $tarea->id,
            'alumno_id' => $escenario['alumno']->id,
        ]);
        $this->assertDatabaseMissing('establecimiento_tarea_destinatarios', [
            'establecimiento_tarea_id' => $tarea->id,
            'alumno_id' => $escenario['companero']->id,
        ]);
    });

    it('returns 404 when another teacher tries to update the homework', function () {
        $escenario = escenarioTareasDocente();
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id]);
        $ajeno = escenarioTareasDocente(['grado' => '9no Básica']);

        $this->actingAs($ajeno['docente'])
            ->put(route('Docente.tareas.update', $tarea), payloadTareaDocente($escenario, [
                'nombre' => 'Intruso',
            ]))
            ->assertNotFound();

        expect($tarea->refresh()->nombre)->toBe('Ensayo de la semana');
    });
});

describe('destroy', function () {
    it('deletes the homework and its files', function () {
        Storage::fake('local');
        $escenario = escenarioTareasDocente();
        Storage::disk('local')->put('tareas/contenido/guia.pdf', 'pdf');
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id], [
            'contenido_tipo' => TipoContenidoTarea::Pdf,
            'contenido_texto' => null,
            'contenido_path' => 'tareas/contenido/guia.pdf',
            'contenido_nombre' => 'guia.pdf',
        ]);

        $this->actingAs($escenario['docente'])
            ->delete(route('Docente.tareas.destroy', $tarea))
            ->assertRedirect(route('Docente.tareas.index'));

        $this->assertDatabaseMissing('establecimiento_tareas', ['id' => $tarea->id]);
        Storage::disk('local')->assertMissing('tareas/contenido/guia.pdf');
    });
});

describe('archivo', function () {
    it('streams private homework content inline for the assigned teacher', function () {
        Storage::fake('local');
        $escenario = escenarioTareasDocente();
        Storage::disk('local')->put('tareas/contenido/guia.pdf', 'contenido-pdf');
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id], [
            'contenido_tipo' => TipoContenidoTarea::Pdf,
            'contenido_texto' => null,
            'contenido_path' => 'tareas/contenido/guia.pdf',
            'contenido_nombre' => 'guia.pdf',
        ]);

        $response = $this->actingAs($escenario['docente'])
            ->get(route('Docente.tareas.archivo', $tarea));

        $response->assertOk();
        expect($response->headers->get('content-disposition'))->toStartWith('inline');
    });

    it('returns 404 when another teacher downloads the file', function () {
        Storage::fake('local');
        $escenario = escenarioTareasDocente();
        Storage::disk('local')->put('tareas/contenido/guia.pdf', 'contenido-pdf');
        $tarea = registrarTareaDocente($escenario, [$escenario['alumno']->id], [
            'contenido_tipo' => TipoContenidoTarea::Pdf,
            'contenido_texto' => null,
            'contenido_path' => 'tareas/contenido/guia.pdf',
            'contenido_nombre' => 'guia.pdf',
        ]);
        $ajeno = escenarioTareasDocente(['grado' => '9no Básica']);

        $this->actingAs($ajeno['docente'])
            ->get(route('Docente.tareas.archivo', $tarea))
            ->assertNotFound();
    });
});
