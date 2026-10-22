<?php

use App\Auth\ActiveDocenteContexto;
use App\Auth\ActiveRole;
use App\Enums\EsquemaCiclo;
use App\Enums\Role;
use App\Enums\TipoAsignatura;
use App\Enums\TipoCalificacion;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAlumnoAsignatura;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\EstablecimientoInsumoActividadCalificacion;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\NavigationItem;
use App\Models\Persona;
use App\Models\Sys_Asignatura;
use App\Models\TipoInsumo;
use App\Models\TipoInsumoDetalle;
use App\Models\User;
use Database\Seeders\NavigationSeeder;
use Illuminate\Support\Facades\DB;

/**
 * @return array{
 *     establecimiento: Establecimiento,
 *     docente: User,
 *     aula: EstablecimientoAula,
 *     malla: EstablecimientoAsignatura,
 *     ciclo: EstablecimientoPeriodoCiclo
 * }
 */
function escenarioCalificarInsumos(array $atributos = []): array
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
    ]);

    return compact('establecimiento', 'docente', 'aula', 'malla', 'ciclo', 'periodo');
}

function guardarContextoCalificar(array $escenario, int $parcial = 1): void
{
    test()->actingAs($escenario['docente'])
        ->post(route('Docente.calificar.store'), [
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'parcial_numero' => $parcial,
        ]);
}

function matricularAlumnoCalificar(EstablecimientoAula $aula, Alumno $alumno): EstablecimientoMatricula
{
    return EstablecimientoMatricula::factory()->create([
        'establecimiento_id' => $aula->establecimiento_id,
        'establecimiento_periodo_id' => $aula->establecimiento_periodo_id,
        'establecimiento_aula_id' => $aula->id,
        'alumno_id' => $alumno->id,
    ]);
}

/**
 * @param  list<array{nombre: string, valor?: string|null, porcentaje?: string|null}>  $detalles
 * @return array{tipo: TipoInsumo, detalles: list<TipoInsumoDetalle>}
 */
function asignarTipoInsumoCalificar(array $escenario, string $tipoCalificacion, array $detalles): array
{
    $tipo = tipoInsumoDe($escenario['periodo'], [
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

function actividadDeCalificar(array $escenario, TipoInsumoDetalle $detalle, string $nombre): EstablecimientoInsumoActividad
{
    $empleadoId = EstablecimientoAulaDocente::query()
        ->where('establecimiento_aula_id', $escenario['aula']->id)
        ->where('establecimiento_asignatura_id', $escenario['malla']->id)
        ->value('empleado_id');

    return EstablecimientoInsumoActividad::factory()->create([
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'parcial_numero' => 1,
        'tipo_insumo_detalle_id' => $detalle->id,
        'empleado_id' => $empleadoId,
        'nombre' => $nombre,
    ]);
}

describe('index', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('Docente.calificar.index'))
            ->assertRedirect(route('login'));
    });

    it('forbids an administrator from opening the grading context', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('Docente.calificar.index'))
            ->assertForbidden();
    });

    it('renders the assigned classroom and subject of the teacher', function () {
        $escenario = escenarioCalificarInsumos();

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertSee('Calificar Nuevos insumos')
            ->assertSee('Aulas:')
            ->assertSee('Asignaturas:')
            ->assertSee('flex-nowrap', false)
            ->assertSee('8vo Básica A')
            ->assertSee('Matemática')
            ->assertSee('Primer quimestre')
            ->assertSee('1er Parcial')
            ->assertSee('2do Parcial');
    });

    it('lists the partials configured on the period even without opened closures', function () {
        $escenario = escenarioCalificarInsumos(['numero_parciales' => 3]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertSee('1er Parcial')
            ->assertSee('2do Parcial')
            ->assertSee('3er Parcial')
            ->assertDontSee('4to Parcial');
    });

    it('does not list a classroom assigned to another teacher', function () {
        $escenario = escenarioCalificarInsumos();
        $ajeno = escenarioCalificarInsumos([
            'grado' => '9no Básica',
            'paralelo' => 'B',
            'asignatura' => 'Lengua',
        ]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertSee('8vo Básica A')
            ->assertDontSee('9no Básica B')
            ->assertDontSee('Lengua');
    });

    it('does not list a classroom of an inactive period', function () {
        $escenario = escenarioCalificarInsumos();
        $escenario['aula']->periodo->update(['activo' => false]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertSee('No tienes aulas asignadas en un periodo activo.')
            ->assertDontSee('8vo Básica A');
    });

    it('labels the cycle list according to the period scheme', function (EsquemaCiclo $esquema, string $etiqueta, string $ciclo) {
        $escenario = escenarioCalificarInsumos([
            'esquema_ciclo' => $esquema,
            'ciclo' => $ciclo,
        ]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertSee($etiqueta)
            ->assertSee($ciclo);
    })->with([
        'quimestres' => [EsquemaCiclo::Quimestres, 'Quimestres', 'Primer quimestre'],
        'trimestres' => [EsquemaCiclo::Trimestres, 'Trimestres', 'Primer trimestre'],
    ]);

    it('shows the calificar insumos link to teachers', function () {
        $this->seed(NavigationSeeder::class);
        $escenario = escenarioCalificarInsumos();

        $this->actingAs($escenario['docente'])
            ->withSession([ActiveRole::SESSION_KEY => Role::Docente->value])
            ->get(route('dashboard'))
            ->assertSee('Insumos')
            ->assertSee(route('navigation.hub', NavigationItem::query()->where('label', 'Insumos')->where('is_group', true)->firstOrFail()), false);
    });

    it('escapes a dangerous subject name in the selector', function () {
        $escenario = escenarioCalificarInsumos([
            'asignatura' => "<script>alert('xss')</script>",
        ]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertDontSee("<script>alert('xss')</script>", false);
    });

    it('forgets an invalid stored context', function () {
        $escenario = escenarioCalificarInsumos();

        $this->actingAs($escenario['docente'])
            ->withSession([
                ActiveDocenteContexto::AULA_KEY => 999999,
                ActiveDocenteContexto::ASIGNATURA_KEY => $escenario['malla']->id,
                ActiveDocenteContexto::CICLO_KEY => $escenario['ciclo']->id,
                ActiveDocenteContexto::PARCIAL_KEY => 1,
            ])
            ->get(route('Docente.calificar.index'))
            ->assertSessionMissing(ActiveDocenteContexto::AULA_KEY)
            ->assertSessionMissing(ActiveDocenteContexto::ASIGNATURA_KEY)
            ->assertSessionMissing(ActiveDocenteContexto::CICLO_KEY)
            ->assertSessionMissing(ActiveDocenteContexto::PARCIAL_KEY);
    });
});

describe('store', function () {
    it('redirects guests to the login page', function () {
        $this->post(route('Docente.calificar.store'))
            ->assertRedirect(route('login'));
    });

    it('forbids an administrator from storing a grading context', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->post(route('Docente.calificar.store'), [
                'establecimiento_aula_id' => 1,
                'establecimiento_asignatura_id' => 1,
                'establecimiento_periodo_ciclo_id' => 1,
                'parcial_numero' => 1,
            ])
            ->assertForbidden();
    });

    it('stores the selected classroom, subject, cycle and partial in the session', function () {
        $escenario = escenarioCalificarInsumos();

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.calificar.index'))
            ->post(route('Docente.calificar.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
            ])
            ->assertRedirect(route('Docente.calificar.index'))
            ->assertSessionHas(ActiveDocenteContexto::AULA_KEY, $escenario['aula']->id)
            ->assertSessionHas(ActiveDocenteContexto::ASIGNATURA_KEY, $escenario['malla']->id)
            ->assertSessionHas(ActiveDocenteContexto::CICLO_KEY, $escenario['ciclo']->id)
            ->assertSessionHas(ActiveDocenteContexto::PARCIAL_KEY, 1);
    });

    it('returns to the edit page when the context is saved from there', function () {
        $escenario = escenarioCalificarInsumos();

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.calificar.editar'))
            ->post(route('Docente.calificar.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
                'destino' => 'editar',
            ])
            ->assertRedirect(route('Docente.calificar.editar'));
    });

    it('shows the stored context on other teacher pages', function () {
        $escenario = escenarioCalificarInsumos();

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.calificar.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
            ]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.horarios'))
            ->assertSee('8vo Básica A')
            ->assertSee('Matemática')
            ->assertSee('Primer quimestre')
            ->assertSee('Parcial 1');
    });

    it('returns 404 when the classroom belongs to another teacher', function () {
        $escenario = escenarioCalificarInsumos();
        $ajeno = escenarioCalificarInsumos(['grado' => '9no Básica']);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.calificar.store'), [
                'establecimiento_aula_id' => $ajeno['aula']->id,
                'establecimiento_asignatura_id' => $ajeno['malla']->id,
                'establecimiento_periodo_ciclo_id' => $ajeno['ciclo']->id,
                'parcial_numero' => 1,
            ])
            ->assertNotFound();
    });

    it('returns 404 when the cycle belongs to another period', function () {
        $escenario = escenarioCalificarInsumos();
        $ajeno = escenarioCalificarInsumos(['grado' => '9no Básica']);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.calificar.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $ajeno['ciclo']->id,
                'parcial_numero' => 1,
            ])
            ->assertNotFound();
    });

    it('redirects back when the selection is empty', function () {
        $escenario = escenarioCalificarInsumos();

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.calificar.index'))
            ->post(route('Docente.calificar.store'), [])
            ->assertRedirect(route('Docente.calificar.index'))
            ->assertSessionHasErrors([
                'establecimiento_aula_id' => 'Selecciona un aula.',
                'establecimiento_asignatura_id' => 'Selecciona una materia.',
                'establecimiento_periodo_ciclo_id' => 'Selecciona un quimestre o trimestre.',
                'parcial_numero' => 'Selecciona un parcial.',
            ]);
    });

    it('returns 404 when the partial is outside the period configuration', function () {
        $escenario = escenarioCalificarInsumos(['numero_parciales' => 2]);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.calificar.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 5,
            ])
            ->assertNotFound();
    });

    it('shows the activity name and configured supply types after the selection', function () {
        $escenario = escenarioCalificarInsumos();
        $tipoInsumo = tipoInsumoDe($escenario['periodo'], ['nombre' => 'Evaluación de aula']);
        $tipoInsumo->detalles()->create([
            'nombre' => 'Arte',
            'porcentaje' => '50.00',
            'activo' => true,
        ]);
        DB::table('establecimiento_aula_asignatura_tipo_insumos')->insert([
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'tipo_insumo_id' => $tipoInsumo->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertSee('Nombre de la actividad')
            ->assertSee('Elegir un tipo de insumo:')
            ->assertSee('Arte (50 %)')
            ->assertSee('Grabar calificaciones')
            ->assertDontSee('Crear actividad');
    });

    it('lists enrolled classroom students with a numeric grade input', function () {
        $escenario = escenarioCalificarInsumos();
        asignarTipoInsumoCalificar($escenario, 'cuantitativa', [
            ['nombre' => 'Arte', 'porcentaje' => '50.00'],
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Cárdenas');
        matricularAlumnoCalificar($escenario['aula'], $alumno);
        guardarContextoCalificar($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertSee('Cárdenas Ana')
            ->assertSee('Calificación')
            ->assertSee('name="notas['.$alumno->id.']"', false)
            ->assertSee('type="number"', false)
            ->assertSee('Grabar calificaciones');
    });

    it('does not list a student enrolled in another classroom', function () {
        $escenario = escenarioCalificarInsumos();
        $otra = escenarioCalificarInsumos(['grado' => '9no Básica']);
        $propio = alumnoDelEstablecimiento($escenario['establecimiento'], 'Aldaz');
        $ajeno = alumnoDelEstablecimiento($otra['establecimiento'], 'Benítez');
        matricularAlumnoCalificar($escenario['aula'], $propio);
        matricularAlumnoCalificar($otra['aula'], $ajeno);
        guardarContextoCalificar($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertSee('Aldaz Ana')
            ->assertDontSee('Benítez Ana');
    });

    it('lists qualitative values configured for the subject', function () {
        $escenario = escenarioCalificarInsumos([
            'tipo_calificacion' => TipoCalificacion::Cualitativa,
        ]);
        asignarTipoInsumoCalificar($escenario, 'cualitativa', [
            ['nombre' => 'A', 'valor' => 'A'],
            ['nombre' => 'B', 'valor' => 'B'],
            ['nombre' => 'C', 'valor' => 'C'],
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Dávila');
        matricularAlumnoCalificar($escenario['aula'], $alumno);
        guardarContextoCalificar($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertSee('Dávila Ana')
            ->assertSee('>A</option>', false)
            ->assertSee('>B</option>', false)
            ->assertSee('>C</option>', false)
            ->assertDontSee('type="number"', false);
    });

    it('lists inscribed students of a floating subject when none are in the selected classroom', function () {
        $escenario = escenarioCalificarInsumos([
            'tipo_asignatura' => TipoAsignatura::Optativa,
            'es_flotante' => true,
        ]);
        $otraAula = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $escenario['establecimiento']->id,
            'establecimiento_modalidad_jornada_id' => $escenario['aula']->establecimiento_modalidad_jornada_id,
            'establecimiento_periodo_id' => $escenario['aula']->establecimiento_periodo_id,
            'establecimiento_grado_id' => $escenario['aula']->establecimiento_grado_id,
            'paralelo' => 'B',
        ]);
        $inscrito = alumnoDelEstablecimiento($escenario['establecimiento'], 'Mendoza');
        matricularAlumnoCalificar($otraAula, $inscrito);
        EstablecimientoAlumnoAsignatura::factory()->create([
            'establecimiento_periodo_id' => $escenario['aula']->establecimiento_periodo_id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'alumno_id' => $inscrito->id,
        ]);
        guardarContextoCalificar($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertSee('Mendoza Ana');
    });

    it('lists only students inscribed in an optional subject', function () {
        $escenario = escenarioCalificarInsumos([
            'tipo_asignatura' => TipoAsignatura::Optativa,
            'es_flotante' => true,
        ]);
        $inscrito = alumnoDelEstablecimiento($escenario['establecimiento'], 'Espinoza');
        $delAula = alumnoDelEstablecimiento($escenario['establecimiento'], 'Flores');
        matricularAlumnoCalificar($escenario['aula'], $inscrito);
        matricularAlumnoCalificar($escenario['aula'], $delAula);
        EstablecimientoAlumnoAsignatura::factory()->create([
            'establecimiento_periodo_id' => $escenario['aula']->establecimiento_periodo_id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'alumno_id' => $inscrito->id,
        ]);
        guardarContextoCalificar($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertSee('Espinoza Ana')
            ->assertDontSee('Flores Ana');
    });

    it('lists every classroom student when a common subject was marked floating', function () {
        $escenario = escenarioCalificarInsumos(['es_flotante' => true]);
        $delGrupo = alumnoDelEstablecimiento($escenario['establecimiento'], 'Espinoza');
        $nuevo = alumnoDelEstablecimiento($escenario['establecimiento'], 'Heredia');
        matricularAlumnoCalificar($escenario['aula'], $delGrupo);
        matricularAlumnoCalificar($escenario['aula'], $nuevo);
        EstablecimientoAlumnoAsignatura::factory()->create([
            'establecimiento_periodo_id' => $escenario['aula']->establecimiento_periodo_id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'alumno_id' => $delGrupo->id,
        ]);
        guardarContextoCalificar($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertSee('Espinoza Ana')
            ->assertSee('Heredia Ana');
    });

    it('tells the teacher when the classroom has no enrolled students', function () {
        $escenario = escenarioCalificarInsumos();
        guardarContextoCalificar($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertSee('No hay alumnos matriculados en este aula o asignatura.');
    });

    it('escapes a dangerous student name in the roster', function () {
        $escenario = escenarioCalificarInsumos();
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], "<script>alert('xss')</script>");
        matricularAlumnoCalificar($escenario['aula'], $alumno);
        guardarContextoCalificar($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertDontSee("<script>alert('xss')</script>", false);
    });

    it('does not list existing activities on the create page', function () {
        $escenario = escenarioCalificarInsumos();
        $insumo = asignarTipoInsumoCalificar($escenario, 'cuantitativa', [
            ['nombre' => 'Arte', 'porcentaje' => '50.00'],
        ]);
        actividadDeCalificar($escenario, $insumo['detalles'][0], 'Arte 1er trime');
        guardarContextoCalificar($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertSee('Calificar Nuevos insumos')
            ->assertDontSee('Seleccione un insumo')
            ->assertDontSee('Arte 1er trime');
    });

    it('tells the teacher when the subject has no supply type', function () {
        $escenario = escenarioCalificarInsumos();

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.index'))
            ->assertSee('Esta materia no tiene un tipo de insumo asignado en el aula.');
    });
});

describe('storeActividad', function () {
    it('redirects guests to the login page', function () {
        $this->post(route('Docente.calificar.actividades.store'))
            ->assertRedirect(route('login'));
    });

    it('creates an activity for the selected classroom and supply type', function () {
        $escenario = escenarioCalificarInsumos();
        $tipoInsumo = tipoInsumoDe($escenario['periodo'], ['nombre' => 'Evaluación de aula']);
        $detalle = $tipoInsumo->detalles()->create([
            'nombre' => 'Arte',
            'porcentaje' => '50.00',
            'activo' => true,
        ]);
        DB::table('establecimiento_aula_asignatura_tipo_insumos')->insert([
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'tipo_insumo_id' => $tipoInsumo->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $respuesta = $this->actingAs($escenario['docente'])
            ->from(route('Docente.calificar.index'))
            ->post(route('Docente.calificar.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
                'nombre' => 'Ensayo de arte',
                'tipo_insumo_detalle_id' => $detalle->id,
            ]);

        $respuesta
            ->assertRedirect(route('Docente.calificar.index'))
            ->assertSessionHas('status', 'calificaciones-grabadas');

        $this->assertDatabaseHas('establecimiento_insumo_actividades', [
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'parcial_numero' => 1,
            'tipo_insumo_detalle_id' => $detalle->id,
            'nombre' => 'Ensayo de arte',
        ]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.editar'))
            ->assertSee('Ensayo de arte')
            ->assertSee('Arte');
    });

    it('returns 404 when the supply type belongs to another subject', function () {
        $escenario = escenarioCalificarInsumos();
        $ajeno = escenarioCalificarInsumos(['grado' => '9no Básica']);
        $tipoInsumo = tipoInsumoDe($ajeno['periodo'], ['nombre' => 'Evaluación ajena']);
        $detalle = $tipoInsumo->detalles()->create([
            'nombre' => 'Deber',
            'porcentaje' => '30.00',
            'activo' => true,
        ]);
        DB::table('establecimiento_aula_asignatura_tipo_insumos')->insert([
            'establecimiento_aula_id' => $ajeno['aula']->id,
            'establecimiento_asignatura_id' => $ajeno['malla']->id,
            'tipo_insumo_id' => $tipoInsumo->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.calificar.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
                'nombre' => 'Ensayo',
                'tipo_insumo_detalle_id' => $detalle->id,
            ])
            ->assertNotFound();
    });

    it('redirects back when the activity name is missing', function () {
        $escenario = escenarioCalificarInsumos();

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.calificar.index'))
            ->post(route('Docente.calificar.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
                'nombre' => '',
                'tipo_insumo_detalle_id' => 1,
            ])
            ->assertRedirect(route('Docente.calificar.index'))
            ->assertSessionHasErrors(['nombre' => 'Escribe el nombre de la actividad.']);
    });

    it('escapes a dangerous activity name in the list', function () {
        $escenario = escenarioCalificarInsumos();
        $tipoInsumo = tipoInsumoDe($escenario['periodo'], ['nombre' => 'Evaluación de aula']);
        $detalle = $tipoInsumo->detalles()->create([
            'nombre' => 'Arte',
            'porcentaje' => '50.00',
            'activo' => true,
        ]);
        DB::table('establecimiento_aula_asignatura_tipo_insumos')->insert([
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'tipo_insumo_id' => $tipoInsumo->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.calificar.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
                'nombre' => "<script>alert('xss')</script>",
                'tipo_insumo_detalle_id' => $detalle->id,
            ]);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.editar'))
            ->assertDontSee("<script>alert('xss')</script>", false);
    });

    it('saves quantitative grades for enrolled students', function () {
        $escenario = escenarioCalificarInsumos();
        $insumo = asignarTipoInsumoCalificar($escenario, 'cuantitativa', [
            ['nombre' => 'Arte', 'porcentaje' => '50.00'],
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Gavilánez');
        matricularAlumnoCalificar($escenario['aula'], $alumno);

        $respuesta = $this->actingAs($escenario['docente'])
            ->from(route('Docente.calificar.index'))
            ->post(route('Docente.calificar.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
                'nombre' => 'Ensayo de arte',
                'tipo_insumo_detalle_id' => $insumo['detalles'][0]->id,
                'notas' => [
                    $alumno->id => '8.5',
                ],
            ]);

        $respuesta
            ->assertRedirect(route('Docente.calificar.index'))
            ->assertSessionHas('status', 'calificaciones-grabadas');

        $this->assertDatabaseHas('establecimiento_insumo_actividad_calificaciones', [
            'alumno_id' => $alumno->id,
            'nota' => '8.50',
        ]);
    });

    it('saves qualitative grades from the configured values', function () {
        $escenario = escenarioCalificarInsumos([
            'tipo_calificacion' => TipoCalificacion::Cualitativa,
        ]);
        $insumo = asignarTipoInsumoCalificar($escenario, 'cualitativa', [
            ['nombre' => 'A', 'valor' => 'A'],
            ['nombre' => 'B', 'valor' => 'B'],
            ['nombre' => 'C', 'valor' => 'C'],
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Herrera');
        matricularAlumnoCalificar($escenario['aula'], $alumno);

        $respuesta = $this->actingAs($escenario['docente'])
            ->post(route('Docente.calificar.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
                'nombre' => 'Participación',
                'tipo_insumo_detalle_id' => $insumo['detalles'][0]->id,
                'notas' => [
                    $alumno->id => 'B',
                ],
            ]);

        $respuesta->assertRedirect(route('Docente.calificar.index'));

        $this->assertDatabaseHas('establecimiento_insumo_actividad_calificaciones', [
            'alumno_id' => $alumno->id,
            'nota' => 'B',
        ]);
    });

    it('rejects a quantitative grade outside the scale', function () {
        $escenario = escenarioCalificarInsumos();
        $insumo = asignarTipoInsumoCalificar($escenario, 'cuantitativa', [
            ['nombre' => 'Arte', 'porcentaje' => '50.00'],
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Ibarra');
        matricularAlumnoCalificar($escenario['aula'], $alumno);

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.calificar.index'))
            ->post(route('Docente.calificar.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
                'nombre' => 'Ensayo',
                'tipo_insumo_detalle_id' => $insumo['detalles'][0]->id,
                'notas' => [
                    $alumno->id => '11',
                ],
            ])
            ->assertRedirect(route('Docente.calificar.index'))
            ->assertSessionHasErrors(['notas.'.$alumno->id => 'La nota nacional va de 0 a 10.']);
    });

    it('rejects a qualitative grade that is not configured', function () {
        $escenario = escenarioCalificarInsumos([
            'tipo_calificacion' => TipoCalificacion::Cualitativa,
        ]);
        $insumo = asignarTipoInsumoCalificar($escenario, 'cualitativa', [
            ['nombre' => 'A', 'valor' => 'A'],
            ['nombre' => 'B', 'valor' => 'B'],
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Jácome');
        matricularAlumnoCalificar($escenario['aula'], $alumno);

        $this->actingAs($escenario['docente'])
            ->from(route('Docente.calificar.index'))
            ->post(route('Docente.calificar.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
                'nombre' => 'Participación',
                'tipo_insumo_detalle_id' => $insumo['detalles'][0]->id,
                'notas' => [
                    $alumno->id => 'Z',
                ],
            ])
            ->assertRedirect(route('Docente.calificar.index'))
            ->assertSessionHasErrors(['notas.'.$alumno->id => 'Selecciona un valor de la escala cualitativa.']);
    });

    it('returns 404 when the grade belongs to a student of another classroom', function () {
        $escenario = escenarioCalificarInsumos();
        $otra = escenarioCalificarInsumos(['grado' => '9no Básica']);
        $insumo = asignarTipoInsumoCalificar($escenario, 'cuantitativa', [
            ['nombre' => 'Arte', 'porcentaje' => '50.00'],
        ]);
        $ajeno = alumnoDelEstablecimiento($otra['establecimiento'], 'León');
        matricularAlumnoCalificar($otra['aula'], $ajeno);

        $this->actingAs($escenario['docente'])
            ->post(route('Docente.calificar.actividades.store'), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
                'nombre' => 'Ensayo',
                'tipo_insumo_detalle_id' => $insumo['detalles'][0]->id,
                'notas' => [
                    $ajeno->id => '8',
                ],
            ])
            ->assertNotFound();
    });

    it('forbids an administrator from creating an activity', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->post(route('Docente.calificar.actividades.store'), [
                'establecimiento_aula_id' => 1,
                'establecimiento_asignatura_id' => 1,
                'establecimiento_periodo_ciclo_id' => 1,
                'parcial_numero' => 1,
                'nombre' => 'Ensayo',
                'tipo_insumo_detalle_id' => 1,
            ])
            ->assertForbidden();
    });
});

describe('editar', function () {
    it('redirects guests to the login page', function () {
        $this->get(route('Docente.calificar.editar'))
            ->assertRedirect(route('login'));
    });

    it('forbids an administrator from opening the edit page', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('Docente.calificar.editar'))
            ->assertForbidden();
    });

    it('groups existing activities under each supply type', function () {
        $escenario = escenarioCalificarInsumos();
        $insumo = asignarTipoInsumoCalificar($escenario, 'cuantitativa', [
            ['nombre' => 'Arte', 'porcentaje' => '50.00'],
            ['nombre' => 'Música', 'porcentaje' => '50.00'],
        ]);
        actividadDeCalificar($escenario, $insumo['detalles'][0], 'Arte 1er trime');
        actividadDeCalificar($escenario, $insumo['detalles'][0], 'Actividad 1');
        actividadDeCalificar($escenario, $insumo['detalles'][1], 'Musica Primer tri');
        guardarContextoCalificar($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.editar'))
            ->assertSee('Editar insumos')
            ->assertSee('Seleccione un insumo')
            ->assertSee('Arte (50 %)')
            ->assertSee('Arte 1er trime')
            ->assertSee('Actividad 1')
            ->assertSee('Música (50 %)')
            ->assertSee('Musica Primer tri');
    });

    it('loads the saved grades of the selected activity', function () {
        $escenario = escenarioCalificarInsumos();
        $insumo = asignarTipoInsumoCalificar($escenario, 'cuantitativa', [
            ['nombre' => 'Arte', 'porcentaje' => '50.00'],
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Naranjo');
        matricularAlumnoCalificar($escenario['aula'], $alumno);
        $actividad = actividadDeCalificar($escenario, $insumo['detalles'][0], 'Arte 1er trime');
        EstablecimientoInsumoActividadCalificacion::factory()->create([
            'establecimiento_insumo_actividad_id' => $actividad->id,
            'alumno_id' => $alumno->id,
            'nota' => '8.50',
        ]);
        guardarContextoCalificar($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.editar', ['actividad' => $actividad->id]))
            ->assertSee('Editar insumos')
            ->assertSee('Arte 1er trime')
            ->assertSee('value="8.50"', false)
            ->assertDontSee('Elegir un tipo de insumo:');
    });

    it('lists new and ungraded students so the teacher can assign a grade', function () {
        $escenario = escenarioCalificarInsumos();
        $insumo = asignarTipoInsumoCalificar($escenario, 'cuantitativa', [
            ['nombre' => 'Arte', 'porcentaje' => '50.00'],
        ]);
        $calificado = alumnoDelEstablecimiento($escenario['establecimiento'], 'Naranjo');
        $sinNota = alumnoDelEstablecimiento($escenario['establecimiento'], 'Benítez');
        $nuevo = alumnoDelEstablecimiento($escenario['establecimiento'], 'Zambrano');
        matricularAlumnoCalificar($escenario['aula'], $calificado);
        matricularAlumnoCalificar($escenario['aula'], $sinNota);
        $actividad = actividadDeCalificar($escenario, $insumo['detalles'][0], 'Arte 1er trime');
        EstablecimientoInsumoActividadCalificacion::factory()->create([
            'establecimiento_insumo_actividad_id' => $actividad->id,
            'alumno_id' => $calificado->id,
            'nota' => '8.50',
        ]);
        matricularAlumnoCalificar($escenario['aula'], $nuevo);
        guardarContextoCalificar($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.editar', ['actividad' => $actividad->id]))
            ->assertSee('Naranjo')
            ->assertSee('Benítez')
            ->assertSee('Zambrano')
            ->assertSee('Sin calificar')
            ->assertSee('También aparecen los alumnos nuevos')
            ->assertSee('value="8.50"', false)
            ->assertSee('name="notas['.$sinNota->id.']"', false)
            ->assertSee('name="notas['.$nuevo->id.']"', false);
    });

    it('lists a newly enrolled classroom student when editing a floating common subject', function () {
        $escenario = escenarioCalificarInsumos(['es_flotante' => true]);
        $insumo = asignarTipoInsumoCalificar($escenario, 'cuantitativa', [
            ['nombre' => 'Arte', 'porcentaje' => '50.00'],
        ]);
        $delGrupo = alumnoDelEstablecimiento($escenario['establecimiento'], 'Espinoza');
        $nuevo = alumnoDelEstablecimiento($escenario['establecimiento'], 'Heredia');
        matricularAlumnoCalificar($escenario['aula'], $delGrupo);
        EstablecimientoAlumnoAsignatura::factory()->create([
            'establecimiento_periodo_id' => $escenario['aula']->establecimiento_periodo_id,
            'establecimiento_asignatura_id' => $escenario['malla']->id,
            'alumno_id' => $delGrupo->id,
        ]);
        $actividad = actividadDeCalificar($escenario, $insumo['detalles'][0], 'Arte 1er trime');
        EstablecimientoInsumoActividadCalificacion::factory()->create([
            'establecimiento_insumo_actividad_id' => $actividad->id,
            'alumno_id' => $delGrupo->id,
            'nota' => '8.50',
        ]);
        matricularAlumnoCalificar($escenario['aula'], $nuevo);
        guardarContextoCalificar($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.editar', ['actividad' => $actividad->id]))
            ->assertSee('Espinoza')
            ->assertSee('Heredia')
            ->assertSee('Sin calificar')
            ->assertSee('name="notas['.$nuevo->id.']"', false);
    });

    it('returns 404 when the selected activity belongs to another teacher', function () {
        $escenario = escenarioCalificarInsumos();
        $ajeno = escenarioCalificarInsumos(['grado' => '9no Básica']);
        $insumo = asignarTipoInsumoCalificar($ajeno, 'cuantitativa', [
            ['nombre' => 'Arte', 'porcentaje' => '50.00'],
        ]);
        $actividad = actividadDeCalificar($ajeno, $insumo['detalles'][0], 'Actividad ajena');
        guardarContextoCalificar($escenario);

        $this->actingAs($escenario['docente'])
            ->get(route('Docente.calificar.editar', ['actividad' => $actividad->id]))
            ->assertNotFound();
    });
});

describe('updateActividad', function () {
    it('redirects guests to the login page', function () {
        $this->put(route('Docente.calificar.actividades.update', 1))
            ->assertRedirect(route('login'));
    });

    it('updates the selected activity name and grades', function () {
        $escenario = escenarioCalificarInsumos();
        $insumo = asignarTipoInsumoCalificar($escenario, 'cuantitativa', [
            ['nombre' => 'Arte', 'porcentaje' => '50.00'],
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Ordoñez');
        matricularAlumnoCalificar($escenario['aula'], $alumno);
        $actividad = actividadDeCalificar($escenario, $insumo['detalles'][0], 'Arte 1er trime');
        EstablecimientoInsumoActividadCalificacion::factory()->create([
            'establecimiento_insumo_actividad_id' => $actividad->id,
            'alumno_id' => $alumno->id,
            'nota' => '7.00',
        ]);

        $this->actingAs($escenario['docente'])
            ->put(route('Docente.calificar.actividades.update', $actividad), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
                'nombre' => 'Arte primer trimestre',
                'notas' => [
                    $alumno->id => '9.25',
                ],
            ])
            ->assertRedirect(route('Docente.calificar.editar', ['actividad' => $actividad->id]))
            ->assertSessionHas('status', 'calificaciones-grabadas');

        $this->assertDatabaseHas('establecimiento_insumo_actividades', [
            'id' => $actividad->id,
            'nombre' => 'Arte primer trimestre',
        ]);
        $this->assertDatabaseHas('establecimiento_insumo_actividad_calificaciones', [
            'establecimiento_insumo_actividad_id' => $actividad->id,
            'alumno_id' => $alumno->id,
            'nota' => '9.25',
        ]);
    });

    it('saves a grade for a student who was not graded before', function () {
        $escenario = escenarioCalificarInsumos();
        $insumo = asignarTipoInsumoCalificar($escenario, 'cuantitativa', [
            ['nombre' => 'Arte', 'porcentaje' => '50.00'],
        ]);
        $calificado = alumnoDelEstablecimiento($escenario['establecimiento'], 'Ordoñez');
        $nuevo = alumnoDelEstablecimiento($escenario['establecimiento'], 'Zambrano');
        matricularAlumnoCalificar($escenario['aula'], $calificado);
        matricularAlumnoCalificar($escenario['aula'], $nuevo);
        $actividad = actividadDeCalificar($escenario, $insumo['detalles'][0], 'Arte 1er trime');
        EstablecimientoInsumoActividadCalificacion::factory()->create([
            'establecimiento_insumo_actividad_id' => $actividad->id,
            'alumno_id' => $calificado->id,
            'nota' => '7.00',
        ]);

        $this->actingAs($escenario['docente'])
            ->put(route('Docente.calificar.actividades.update', $actividad), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
                'nombre' => 'Arte 1er trime',
                'notas' => [
                    $calificado->id => '7.00',
                    $nuevo->id => '8.00',
                ],
            ])
            ->assertRedirect(route('Docente.calificar.editar', ['actividad' => $actividad->id]))
            ->assertSessionHas('status', 'calificaciones-grabadas');

        $this->assertDatabaseHas('establecimiento_insumo_actividad_calificaciones', [
            'establecimiento_insumo_actividad_id' => $actividad->id,
            'alumno_id' => $calificado->id,
            'nota' => '7.00',
        ]);
        $this->assertDatabaseHas('establecimiento_insumo_actividad_calificaciones', [
            'establecimiento_insumo_actividad_id' => $actividad->id,
            'alumno_id' => $nuevo->id,
            'nota' => '8.00',
        ]);
    });

    it('clears a saved grade when the teacher leaves it empty', function () {
        $escenario = escenarioCalificarInsumos();
        $insumo = asignarTipoInsumoCalificar($escenario, 'cuantitativa', [
            ['nombre' => 'Arte', 'porcentaje' => '50.00'],
        ]);
        $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Paredes');
        matricularAlumnoCalificar($escenario['aula'], $alumno);
        $actividad = actividadDeCalificar($escenario, $insumo['detalles'][0], 'Actividad 1');
        EstablecimientoInsumoActividadCalificacion::factory()->create([
            'establecimiento_insumo_actividad_id' => $actividad->id,
            'alumno_id' => $alumno->id,
            'nota' => '6.00',
        ]);

        $this->actingAs($escenario['docente'])
            ->put(route('Docente.calificar.actividades.update', $actividad), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
                'nombre' => 'Actividad 1',
                'notas' => [
                    $alumno->id => '',
                ],
            ])
            ->assertRedirect(route('Docente.calificar.editar', ['actividad' => $actividad->id]));

        $this->assertDatabaseMissing('establecimiento_insumo_actividad_calificaciones', [
            'establecimiento_insumo_actividad_id' => $actividad->id,
            'alumno_id' => $alumno->id,
        ]);
    });

    it('returns 404 when updating an activity of another teacher', function () {
        $escenario = escenarioCalificarInsumos();
        $ajeno = escenarioCalificarInsumos(['grado' => '9no Básica']);
        $insumo = asignarTipoInsumoCalificar($ajeno, 'cuantitativa', [
            ['nombre' => 'Arte', 'porcentaje' => '50.00'],
        ]);
        $actividad = actividadDeCalificar($ajeno, $insumo['detalles'][0], 'Actividad ajena');

        $this->actingAs($escenario['docente'])
            ->put(route('Docente.calificar.actividades.update', $actividad), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
                'nombre' => 'Cambiada',
            ])
            ->assertNotFound();
    });

    it('forbids an administrator from updating an activity', function () {
        $escenario = escenarioCalificarInsumos();
        $insumo = asignarTipoInsumoCalificar($escenario, 'cuantitativa', [
            ['nombre' => 'Arte', 'porcentaje' => '50.00'],
        ]);
        $actividad = actividadDeCalificar($escenario, $insumo['detalles'][0], 'Actividad 1');
        $admin = adminOf($escenario['establecimiento']);

        $this->actingAs($admin)
            ->put(route('Docente.calificar.actividades.update', $actividad), [
                'establecimiento_aula_id' => $escenario['aula']->id,
                'establecimiento_asignatura_id' => $escenario['malla']->id,
                'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
                'parcial_numero' => 1,
                'nombre' => 'Cambiada',
            ])
            ->assertForbidden();
    });
});
