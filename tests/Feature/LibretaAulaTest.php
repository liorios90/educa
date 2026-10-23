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
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\EstablecimientoInsumoActividadCalificacion;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\Persona;
use App\Models\Sys_Asignatura;
use App\Models\TipoInsumo;
use App\Models\TipoInsumoDetalle;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

it('redirects guests to the login page', function () {
    $this->get(route('secretaria.libreta.index'))
        ->assertRedirect(route('login'));
});

it('forbids roles other than secretary from opening the gradebook', function (User $user) {
    $this->actingAs($user)
        ->get(route('secretaria.libreta.index'))
        ->assertForbidden();
})->with([
    'administrator' => fn () => adminOf(Establecimiento::factory()->create()),
    'teacher' => fn () => assignRole(User::factory()->create([
        'establecimiento_id' => Establecimiento::factory()->create()->id,
    ]), Role::Docente),
]);

it('lists every classroom of the active period before a classroom is chosen', function () {
    $escenario = escenarioLibreta();

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta.index'))
        ->assertSee('Libreta de calificaciones')
        ->assertSee('8vo Básica A')
        ->assertSee('8vo Básica B')
        ->assertDontSee('Matemática');
});

it('shows student notes and the weighted period average of the selected classroom', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Herrera');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    ['detalle' => $detalle] = asignarTipoInsumoLibreta($escenario);
    actividadLibreta($escenario, $detalle, $alumno, 'Tarea 1', '8.00');
    $examen = EstablecimientoCierreActividad::factory()->create([
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'tipo' => TipoCierreEvaluacion::Examen,
        'empleado_id' => $escenario['empleado']->id,
        'nombre' => 'Examen primer quimestre',
    ]);
    $examen->calificaciones()->create([
        'alumno_id' => $alumno->id,
        'nota' => '10.00',
    ]);
    $examenFinal = EstablecimientoCierreActividad::factory()->create([
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'tipo' => TipoCierreEvaluacion::ExamenFinal,
        'empleado_id' => $escenario['empleado']->id,
        'nombre' => 'Examen final del periodo',
    ]);
    $examenFinal->calificaciones()->create([
        'alumno_id' => $alumno->id,
        'nota' => '9.00',
    ]);

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
        ]))
        ->assertSee('Matemática')
        ->assertSee('Herrera Ana')
        ->assertSee('Primer quimestre')
        ->assertSee('Insumos')
        ->assertSee('Examen')
        ->assertSee('Examen final')
        ->assertSee('href="#insumos-'.$escenario['malla']->id.'-'.$escenario['ciclo']->id.'-'.$alumno->id.'"', false)
        ->assertSee('Tarea 1')
        ->assertDontSee('>Tarea 1<', false)
        ->assertSee('8.00')
        ->assertSee('10.00')
        ->assertSee('9.00')
        ->assertSee('8.60')
        ->assertSee('8.68');
});

it('averages insumos of the same type and prefers the subject cycle weights', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Castro');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    ['detalles' => $detalles] = asignarTipoInsumoLibreta($escenario, [
        ['nombre' => 'Tarea', 'porcentaje' => '30.00'],
        ['nombre' => 'Lección', 'porcentaje' => '70.00'],
    ]);
    EstablecimientoAulaAsignaturaCiclo::factory()->create([
        'establecimiento_periodo_id' => $escenario['aula']->establecimiento_periodo_id,
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'ciclo_orden' => $escenario['ciclo']->orden,
        'porcentaje_insumos' => '50.00',
        'porcentaje_examen' => '50.00',
        'porcentaje_proyecto' => '0.00',
    ]);
    actividadLibreta($escenario, $detalles[0], $alumno, 'Tarea 1', '8.00');
    actividadLibreta($escenario, $detalles[0], $alumno, 'Tarea 2', '10.00');
    actividadLibreta($escenario, $detalles[1], $alumno, 'Lección 1', '6.00');
    $examen = EstablecimientoCierreActividad::factory()->create([
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'tipo' => TipoCierreEvaluacion::Examen,
        'empleado_id' => $escenario['empleado']->id,
        'nombre' => 'Examen primer quimestre',
    ]);
    $examen->calificaciones()->create([
        'alumno_id' => $alumno->id,
        'nota' => '10.00',
    ]);

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
        ]))
        ->assertSee('Insumos')
        ->assertSee('50 %')
        ->assertSee('6.90')
        ->assertSee('8.45')
        ->assertSee('href="#insumos-'.$escenario['malla']->id.'-'.$escenario['ciclo']->id.'-'.$alumno->id.'"', false)
        ->assertSee('abrir-libreta-insumos', false)
        ->assertSee('Tarea')
        ->assertSee('Lección')
        ->assertSee('Tarea 1')
        ->assertSee('Tarea 2')
        ->assertSee('Lección 1')
        ->assertDontSee('>Tarea 1<', false)
        ->assertDontSee('>Lección 1<', false)
        ->assertDontSee('7.83')
        ->assertDontSee('9.30');
});

it('keeps the configured insumo percentage when other types have no notes', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Espinoza');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    ['detalles' => $detalles] = asignarTipoInsumoLibreta($escenario, [
        ['nombre' => 'Tarea', 'porcentaje' => '30.00'],
        ['nombre' => 'Lección', 'porcentaje' => '30.00'],
        ['nombre' => 'Proyecto', 'porcentaje' => '40.00'],
    ]);
    actividadLibreta($escenario, $detalles[0], $alumno, 'Tarea 1', '10.00');
    EstablecimientoInsumoActividad::factory()->create([
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'parcial_numero' => 1,
        'tipo_insumo_detalle_id' => $detalles[1]->id,
        'empleado_id' => $escenario['empleado']->id,
        'nombre' => 'Lección vacía',
    ]);
    EstablecimientoInsumoActividad::factory()->create([
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'parcial_numero' => 1,
        'tipo_insumo_detalle_id' => $detalles[2]->id,
        'empleado_id' => $escenario['empleado']->id,
        'nombre' => 'Proyecto vacío',
    ]);

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
        ]))
        ->assertSee('>3.00</a>', false)
        ->assertDontSee('>10.00</a>', false);
});

it('does not show a classroom from another school', function () {
    $escenario = escenarioLibreta();
    $ajeno = escenarioLibreta(['grado' => '9no Básica']);

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta.index'))
        ->assertSee('8vo Básica A')
        ->assertDontSee('9no Básica');

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta.index', [
            'establecimiento_aula_id' => $ajeno['aula']->id,
        ]))
        ->assertNotFound();
});

it('escapes dangerous characters in the student name', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], '<script>alert(1)</script>');
    matricularAlumnoLibreta($escenario['aula'], $alumno);

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
        ]))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});

it('escapes dangerous characters in the insumo activity name', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Mora');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    ['detalle' => $detalle] = asignarTipoInsumoLibreta($escenario);
    actividadLibreta($escenario, $detalle, $alumno, '<script>alert(1)</script>', '8.00');

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
        ]))
        ->assertDontSee('<script>alert(1)</script>', false);
});
