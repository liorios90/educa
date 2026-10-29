<?php

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Auth\ActiveRole;
use App\Enums\EsquemaCiclo;
use App\Enums\Role;
use App\Enums\TipoAsignatura;
use App\Enums\TipoCalificacion;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\Equivalencia;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAsignaturaPropia;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\EstablecimientoInsumoActividadCalificacion;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\Padre;
use App\Models\Persona;
use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Grado;
use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use App\Models\TipoInsumo;
use App\Models\TipoInsumoDetalle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as RoleModel;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function assignRole(User $user, Role $role): User
{
    RoleModel::findOrCreate($role->value, 'web');
    $user->assignRole($role);

    return $user;
}

function cuentaAlumnoDe(Alumno $alumno, Establecimiento $establecimiento): User
{
    $user = assignRole(User::factory()->create([
        'name' => 'Alumno Vega',
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Alumno);

    $alumno->persona->update(['user_id' => $user->id]);

    return $user;
}

function cuentaPadreDe(Establecimiento $establecimiento, Alumno ...$alumnos): User
{
    $user = assignRole(User::factory()->create([
        'name' => 'Padre Vega',
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Padre);
    $persona = Persona::factory()->create([
        'user_id' => $user->id,
        'establecimiento_id' => $establecimiento->id,
    ]);
    $padre = Padre::factory()->create(['persona_id' => $persona->id]);

    foreach ($alumnos as $alumno) {
        $alumno->update(['padre_id' => $padre->id]);
    }

    return $user;
}

function adminOf(Establecimiento $establecimiento): User
{
    return assignRole(User::factory()->create([
        'name' => 'Director Andino',
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Admin);
}

function secretariaOf(Establecimiento $establecimiento): User
{
    return assignRole(User::factory()->create([
        'name' => 'Secretaria Andina',
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Secretaria);
}

/**
 * @return array{nivel: Sys_Nivel, subnivel: Sys_Subnivel, grado: Sys_Grado}
 */
function catalogoEstructura(array $nombres = []): array
{
    $nivel = Sys_Nivel::factory()->create(['nombre' => $nombres['nivel'] ?? 'Educación Inicial']);
    $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create(['nombre' => $nombres['subnivel'] ?? 'Inicial 2']);
    $grado = Sys_Grado::factory()->for($subnivel, 'subnivel')->create(['nombre' => $nombres['grado'] ?? 'Segundo de inicial']);

    return compact('nivel', 'subnivel', 'grado');
}

function ofertaDe(Establecimiento $establecimiento, array $nombres = []): EstablecimientoModalidadJornada
{
    $modalidadNombre = $nombres['modalidad'] ?? 'Presencial';
    $catalogoModalidad = Sys_Modalidad::query()->where('nombre', $modalidadNombre)->first()
        ?? Sys_Modalidad::factory()->create(['nombre' => $modalidadNombre]);

    $establecimientoModalidad = EstablecimientoModalidad::query()->firstOrCreate([
        'establecimiento_id' => $establecimiento->id,
        'modalidad_id' => $catalogoModalidad->id,
    ]);

    $jornadaNombre = $nombres['jornada'] ?? 'Matutina';
    $catalogoJornada = Sys_Jornada::query()->where('nombre', $jornadaNombre)->first()
        ?? Sys_Jornada::factory()->create(['nombre' => $jornadaNombre]);

    return EstablecimientoModalidadJornada::factory()->create([
        'establecimiento_modalidad_id' => $establecimientoModalidad->id,
        'jornada_id' => $catalogoJornada->id,
    ]);
}

/**
 * @return array{0: EstablecimientoModalidadJornada, 1: Sys_Asignatura}
 */
function especialidadEnJornada(Establecimiento $establecimiento, string $nombre): array
{
    $oferta = ofertaDe($establecimiento);
    $asignatura = Sys_Asignatura::factory()->create(['nombre' => $nombre]);
    EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'asignatura_id' => $asignatura->id,
    ]);

    return [$oferta, $asignatura];
}

/**
 * @return array{0: EstablecimientoModalidadJornada, 1: Sys_Asignatura, 2: Sys_Asignatura}
 */
function especialidadesDeUnArea(Establecimiento $establecimiento): array
{
    $oferta = ofertaDe($establecimiento);
    $elemental = Sys_Subnivel::factory()->create(['nombre' => 'Básica Elemental']);
    $media = Sys_Subnivel::factory()->create(['nombre' => 'Básica Media']);
    $areaElemental = Sys_Area::factory()->create([
        'nombre' => 'Matemática',
        'orden' => 1,
        'subnivel_id' => $elemental->id,
    ]);
    $areaMedia = Sys_Area::factory()->create([
        'nombre' => 'Matemática',
        'orden' => 1,
        'subnivel_id' => $media->id,
    ]);
    $matematicaElemental = Sys_Asignatura::factory()->create([
        'nombre' => 'Matemática',
        'area_id' => $areaElemental->id,
    ]);
    $matematicaMedia = Sys_Asignatura::factory()->create([
        'nombre' => 'Matemática',
        'area_id' => $areaMedia->id,
    ]);

    foreach ([$matematicaElemental, $matematicaMedia] as $asignatura) {
        EstablecimientoAsignatura::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'asignatura_id' => $asignatura->id,
        ]);
    }

    return [$oferta, $matematicaElemental, $matematicaMedia];
}

/**
 * @return array<string, int>
 */
function activeOfertaSession(EstablecimientoModalidadJornada $oferta): array
{
    return [ActiveOferta::SESSION_KEY => $oferta->id];
}

function periodoActivoDe(EstablecimientoModalidadJornada $oferta, array $atributos = []): EstablecimientoPeriodo
{
    $oferta->loadMissing('establecimientoModalidad');

    return EstablecimientoPeriodo::factory()->activo()->create([
        'establecimiento_id' => $oferta->establecimientoModalidad->establecimiento_id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        ...$atributos,
    ]);
}

function tipoInsumoDe(EstablecimientoPeriodo $periodo, array $atributos = []): TipoInsumo
{
    return TipoInsumo::query()->create([
        'nombre' => 'Evaluación',
        'tipo_calificacion' => 'cuantitativa',
        'activo' => true,
        'establecimiento_periodo_id' => $periodo->id,
        ...$atributos,
    ]);
}

function equivalenciaDe(EstablecimientoPeriodo $periodo, array $atributos = []): Equivalencia
{
    return Equivalencia::query()->create([
        'nombre' => 'Cualitativa MINEDUC',
        'activo' => true,
        'establecimiento_periodo_id' => $periodo->id,
        ...$atributos,
    ]);
}

/**
 * @return array<string, int>
 */
function activePeriodoSession(EstablecimientoPeriodo $periodo): array
{
    return [ActivePeriodo::SESSION_KEY => $periodo->id];
}

/**
 * @return array<string, mixed>
 */
function cursoDeOctavo(): array
{
    $establecimiento = Establecimiento::factory()->create();
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta, ['numero_parciales' => 2]);
    ['grado' => $catalogo] = catalogoEstructura(['grado' => 'Octavo de básica']);
    $grado = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'subnivel_id' => $catalogo->subnivel_id,
        'grado_id' => $catalogo->id,
        'nombre' => 'Octavo de básica',
    ]);
    $aulaA = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $grado->id,
        'paralelo' => 'A',
    ]);
    $aulaB = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $grado->id,
        'paralelo' => 'B',
    ]);
    $matematica = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $catalogo->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Matemática'])->id,
        'tipo_asignatura' => TipoAsignatura::TroncoComun,
        'aparece_en_libreta' => true,
    ]);
    $educacionFisica = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $catalogo->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Educación Física'])->id,
        'tipo_asignatura' => TipoAsignatura::TroncoComun,
        'aparece_en_libreta' => true,
    ]);
    $optativa = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $catalogo->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Emprendimiento y Gestión'])->id,
        'tipo_asignatura' => TipoAsignatura::Optativa,
        'aparece_en_libreta' => true,
    ]);
    $propia = EstablecimientoAsignaturaPropia::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'nombre' => 'Matemáticas AA NS',
    ]);
    $bachillerato = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $catalogo->id,
        'asignatura_id' => null,
        'asignatura_propia_id' => $propia->id,
        'tipo_asignatura' => TipoAsignatura::Optativa,
        'aparece_en_libreta' => true,
    ]);
    EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'orden' => 1,
        'nombre' => 'Primer quimestre',
    ]);

    return compact(
        'establecimiento',
        'oferta',
        'periodo',
        'catalogo',
        'aulaA',
        'aulaB',
        'matematica',
        'educacionFisica',
        'optativa',
        'bachillerato',
    );
}

/**
 * @return array{
 *     establecimiento: Establecimiento,
 *     secretaria: User,
 *     oferta: EstablecimientoModalidadJornada,
 *     periodo: EstablecimientoPeriodo,
 *     aula: EstablecimientoAula,
 *     aulaB: EstablecimientoAula,
 *     malla: EstablecimientoAsignatura,
 *     ciclo: EstablecimientoPeriodoCiclo,
 *     empleado: Empleado
 * }
 */
function escenarioLibreta(array $atributos = []): array
{
    $establecimiento = Establecimiento::factory()->create();
    $secretaria = secretariaOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta, [
        'esquema_ciclo' => $atributos['esquema_ciclo'] ?? EsquemaCiclo::Quimestres,
        'numero_parciales' => $atributos['numero_parciales'] ?? 2,
        'porcentaje_examen_final' => $atributos['porcentaje_examen_final'] ?? '20.00',
        'porcentaje_proyecto_final' => $atributos['porcentaje_proyecto_final'] ?? '0.00',
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
        'nombre' => $atributos['grado'] ?? '8vo Básica',
    ]);
    $aula = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $grado->id,
        'paralelo' => 'A',
    ]);
    $aulaB = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $grado->id,
        'paralelo' => 'B',
    ]);
    $malla = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $catalogo->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => $atributos['asignatura'] ?? 'Matemática'])->id,
        'tipo_calificacion' => $atributos['tipo_calificacion'] ?? TipoCalificacion::Cuantitativa,
        'tipo_asignatura' => $atributos['tipo_asignatura'] ?? TipoAsignatura::TroncoComun,
    ]);
    $persona = Persona::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'nombres' => 'Ana',
        'apellidos' => 'Perez',
    ]);
    $empleado = Empleado::factory()->for($persona)->create();
    EstablecimientoAulaDocente::factory()->create([
        'establecimiento_aula_id' => $aula->id,
        'establecimiento_asignatura_id' => $malla->id,
        'empleado_id' => $empleado->id,
    ]);
    $ciclo = EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'orden' => 1,
        'nombre' => $atributos['ciclo'] ?? 'Primer quimestre',
        'porcentaje' => $atributos['porcentaje_ciclo'] ?? '80.00',
        'porcentaje_insumos' => $atributos['porcentaje_insumos'] ?? '70.00',
        'porcentaje_examen' => $atributos['porcentaje_examen'] ?? '30.00',
        'porcentaje_proyecto' => $atributos['porcentaje_proyecto'] ?? '0.00',
    ]);

    return compact(
        'establecimiento',
        'secretaria',
        'oferta',
        'periodo',
        'aula',
        'aulaB',
        'malla',
        'ciclo',
        'empleado',
    );
}

function sesionLibreta(array $escenario): array
{
    return [
        ActiveRole::SESSION_KEY => Role::Secretaria->value,
        ...activeContextSession($escenario['oferta'], $escenario['periodo']),
    ];
}

function matricularAlumnoLibreta(EstablecimientoAula $aula, Alumno $alumno): EstablecimientoMatricula
{
    return EstablecimientoMatricula::factory()->create([
        'establecimiento_id' => $aula->establecimiento_id,
        'establecimiento_periodo_id' => $aula->establecimiento_periodo_id,
        'establecimiento_aula_id' => $aula->id,
        'alumno_id' => $alumno->id,
    ]);
}

/**
 * @param  list<array{nombre: string, porcentaje: string}>  $detalles
 * @return array{tipo: TipoInsumo, detalle: TipoInsumoDetalle, detalles: list<TipoInsumoDetalle>}
 */
function asignarTipoInsumoLibreta(array $escenario, array $detalles = []): array
{
    $tipo = tipoInsumoDe($escenario['periodo'], [
        'nombre' => 'Evaluación de aula',
        'tipo_calificacion' => TipoCalificacion::Cuantitativa->value,
    ]);
    $creados = [];

    foreach ($detalles === [] ? [['nombre' => 'Tarea', 'porcentaje' => '100.00']] : $detalles as $detalle) {
        $creados[] = $tipo->detalles()->create([
            'nombre' => $detalle['nombre'],
            'porcentaje' => $detalle['porcentaje'],
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
        'detalle' => $creados[0],
        'detalles' => $creados,
    ];
}

function actividadLibreta(
    array $escenario,
    TipoInsumoDetalle $detalle,
    Alumno $alumno,
    string $nombre,
    string $nota,
): EstablecimientoInsumoActividad {
    $actividad = EstablecimientoInsumoActividad::factory()->create([
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'parcial_numero' => 1,
        'tipo_insumo_detalle_id' => $detalle->id,
        'empleado_id' => $escenario['empleado']->id,
        'nombre' => $nombre,
    ]);
    EstablecimientoInsumoActividadCalificacion::factory()->create([
        'establecimiento_insumo_actividad_id' => $actividad->id,
        'alumno_id' => $alumno->id,
        'nota' => $nota,
    ]);

    return $actividad;
}

function actividadLibretaSinNota(
    array $escenario,
    TipoInsumoDetalle $detalle,
    string $nombre,
    ?Alumno $alumno = null,
    string $nota = '',
): EstablecimientoInsumoActividad {
    $actividad = EstablecimientoInsumoActividad::factory()->create([
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'parcial_numero' => 1,
        'tipo_insumo_detalle_id' => $detalle->id,
        'empleado_id' => $escenario['empleado']->id,
        'nombre' => $nombre,
    ]);

    if ($alumno instanceof Alumno) {
        EstablecimientoInsumoActividadCalificacion::factory()->create([
            'establecimiento_insumo_actividad_id' => $actividad->id,
            'alumno_id' => $alumno->id,
            'nota' => $nota,
        ]);
    }

    return $actividad;
}

function alumnoDelEstablecimiento(Establecimiento $establecimiento, string $apellidos): Alumno
{
    $persona = Persona::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'apellidos' => $apellidos,
        'nombres' => 'Ana',
    ]);

    return Alumno::factory()->create(['persona_id' => $persona->id]);
}

function activeContextSession(EstablecimientoModalidadJornada $oferta, EstablecimientoPeriodo $periodo): array
{
    return [
        ...activeOfertaSession($oferta),
        ...activePeriodoSession($periodo),
    ];
}
