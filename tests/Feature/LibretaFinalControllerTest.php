<?php

use App\Enums\Role;
use App\Enums\TipoCierreEvaluacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoCierreActividad;
use App\Models\User;

it('redirects guests to the login page', function () {
    $this->get(route('secretaria.libreta-final.index'))
        ->assertRedirect(route('login'));
});

it('forbids roles other than secretary from opening the final gradebook', function (User $user) {
    $this->actingAs($user)
        ->get(route('secretaria.libreta-final.index'))
        ->assertForbidden();
})->with([
    'administrator' => fn () => adminOf(Establecimiento::factory()->create()),
    'teacher' => fn () => assignRole(User::factory()->create([
        'establecimiento_id' => Establecimiento::factory()->create()->id,
    ]), Role::Docente),
]);

it('lists classrooms before generating the final gradebook', function () {
    $escenario = escenarioLibreta();

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta-final.index'))
        ->assertSee('Libreta final')
        ->assertSee('8vo Básica A')
        ->assertSee('8vo Básica B')
        ->assertDontSee('Libreta final de calificaciones')
        ->assertDontSee('Matemática');
});

it('shows cycle and annual averages with the MINEDUC qualitative band', function () {
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
        ->get(route('secretaria.libreta-final.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
        ]))
        ->assertSee('Libreta final de calificaciones')
        ->assertSee('Herrera Ana')
        ->assertSee('Matemática')
        ->assertSee('Primer quimestre')
        ->assertSee('Examen final')
        ->assertSee('8.60')
        ->assertSee('9.00')
        ->assertSee('8.68')
        ->assertSee('Alcanza los aprendizajes requeridos');
});

it('does not include a blank insumo in the annual average', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Naranjo');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    ['detalle' => $detalle] = asignarTipoInsumoLibreta($escenario);
    actividadLibreta($escenario, $detalle, $alumno, 'Tarea 1', '8.00');
    actividadLibretaSinNota($escenario, $detalle, 'Tarea en blanco');

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta-final.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
        ]))
        ->assertSeeInOrder(['Matemática', '5.60', '—', '5.60'])
        ->assertDontSee('8.00')
        ->assertDontSee('4.00');
});

it('does not show a classroom from another school', function () {
    $escenario = escenarioLibreta();
    $ajeno = escenarioLibreta(['grado' => '9no Básica']);

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta-final.index'))
        ->assertSee('8vo Básica A')
        ->assertDontSee('9no Básica');

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta-final.index', [
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
        ->get(route('secretaria.libreta-final.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
        ]))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});
