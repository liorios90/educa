<?php

use App\Enums\EsquemaCiclo;
use App\Enums\EstadoAsistencia;
use App\Enums\Role;
use App\Enums\TipoCierreEvaluacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsistencia;
use App\Models\EstablecimientoCierreActividad;
use App\Models\EstablecimientoComportamiento;
use App\Models\User;

/**
 * @param  array<string, mixed>  $escenario
 * @return array<string, int|string>
 */
function parametrosInformeAprendizaje(array $escenario, string $tipo = 'parcial', ?int $parcial = 1): array
{
    $parametros = [
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'tipo' => $tipo,
    ];

    if ($tipo === 'parcial') {
        $parametros['parcial_numero'] = $parcial ?? 1;
    }

    return $parametros;
}

it('redirects guests to the login page', function () {
    $this->get(route('secretaria.informes-aprendizaje.index'))
        ->assertRedirect(route('login'));
});

it('forbids roles other than secretary from opening the learning reports', function (User $user) {
    $this->actingAs($user)
        ->get(route('secretaria.informes-aprendizaje.index'))
        ->assertForbidden();
})->with([
    'administrator' => fn () => adminOf(Establecimiento::factory()->create()),
    'teacher' => fn () => assignRole(User::factory()->create([
        'establecimiento_id' => Establecimiento::factory()->create()->id,
    ]), Role::Docente),
]);

it('lists classrooms and report filters before generating a report', function () {
    $escenario = escenarioLibreta();

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.informes-aprendizaje.index'))
        ->assertSee('Informes de aprendizaje')
        ->assertSee('8vo Básica A')
        ->assertSee('8vo Básica B')
        ->assertSee('Primer quimestre')
        ->assertSee('Informe parcial')
        ->assertSee('Informe quimestral')
        ->assertDontSee('Informe parcial de aprendizaje')
        ->assertDontSee('Matemática');
});

it('shows only the selected partial grade and the MINEDUC qualitative band', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Herrera');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    ['detalle' => $detalle] = asignarTipoInsumoLibreta($escenario);
    actividadLibreta($escenario, $detalle, $alumno, 'Tarea 1', '8.00');
    $segundo = actividadLibreta($escenario, $detalle, $alumno, 'Tarea 2', '10.00');
    $segundo->update(['parcial_numero' => 2]);
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
        ->get(route('secretaria.informes-aprendizaje.index', parametrosInformeAprendizaje($escenario)))
        ->assertSee('Informe parcial de aprendizaje')
        ->assertSee('1er parcial')
        ->assertSee('Herrera Ana')
        ->assertSee('Matemática')
        ->assertSee('8.00')
        ->assertSee('Alcanza los aprendizajes requeridos')
        ->assertDontSee('10.00')
        ->assertDontSee('9.00')
        ->assertDontSee('8.60');
});

it('includes the exam in the quimestral report of the selected cycle', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Castro');
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

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.informes-aprendizaje.index', parametrosInformeAprendizaje($escenario, 'ciclo')))
        ->assertSee('Informe quimestral de aprendizaje')
        ->assertSee('Castro Ana')
        ->assertSee('8.60')
        ->assertSee('Alcanza los aprendizajes requeridos')
        ->assertDontSee('Informe parcial de aprendizaje');
});

it('does not include a blank insumo in the partial report average', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Naranjo');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    ['detalle' => $detalle] = asignarTipoInsumoLibreta($escenario);
    actividadLibreta($escenario, $detalle, $alumno, 'Tarea 1', '8.00');
    actividadLibretaSinNota($escenario, $detalle, 'Tarea en blanco');

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.informes-aprendizaje.index', parametrosInformeAprendizaje($escenario)))
        ->assertSee('8.00')
        ->assertDontSee('4.00');
});

it('titles the cycle report as trimestral when the period uses trimesters', function () {
    $escenario = escenarioLibreta([
        'esquema_ciclo' => EsquemaCiclo::Trimestres,
        'ciclo' => 'Primer trimestre',
    ]);
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Mora');
    matricularAlumnoLibreta($escenario['aula'], $alumno);

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.informes-aprendizaje.index', parametrosInformeAprendizaje($escenario, 'ciclo')))
        ->assertSee('Informe trimestral de aprendizaje')
        ->assertSee('Primer trimestre')
        ->assertDontSee('Informe quimestral de aprendizaje');
});

it('shows attendance of the selected partial and the cycle behavior grade', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Andrade');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    EstablecimientoAsistencia::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'alumno_id' => $alumno->id,
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'parcial_numero' => 1,
        'fecha' => '2026-10-01',
        'estado' => EstadoAsistencia::Falta,
    ]);
    EstablecimientoAsistencia::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'alumno_id' => $alumno->id,
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'parcial_numero' => 2,
        'fecha' => '2026-10-15',
        'estado' => EstadoAsistencia::Falta,
    ]);
    EstablecimientoComportamiento::factory()->create([
        'establecimiento_id' => $escenario['establecimiento']->id,
        'alumno_id' => $alumno->id,
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
    ]);

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.informes-aprendizaje.index', parametrosInformeAprendizaje($escenario)))
        ->assertSee('Andrade Ana')
        ->assertSee('Faltas injustificadas: 1')
        ->assertSee('Satisfactorio');
});

it('does not show a classroom from another school', function () {
    $escenario = escenarioLibreta();
    $ajeno = escenarioLibreta(['grado' => '9no Básica']);

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.informes-aprendizaje.index'))
        ->assertSee('8vo Básica A')
        ->assertDontSee('9no Básica');

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.informes-aprendizaje.index', parametrosInformeAprendizaje($ajeno)))
        ->assertNotFound();
});

it('escapes dangerous characters in the student name', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], '<script>alert(1)</script>');
    matricularAlumnoLibreta($escenario['aula'], $alumno);

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.informes-aprendizaje.index', parametrosInformeAprendizaje($escenario)))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});
