<?php

use App\Enums\ModoDecimal;
use App\Enums\Role;
use App\Enums\TipoCierreEvaluacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAulaAsignaturaCiclo;
use App\Models\EstablecimientoCierreActividad;
use App\Models\User;

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
        ->assertSee('5.60')
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
        ->assertSee('3.45')
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

it('shows the configured percentage of the 100 percent insumo value in pond', function () {
    $escenario = escenarioLibreta([
        'porcentaje_insumos' => '30.00',
        'porcentaje_examen' => '70.00',
    ]);
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Espinoza');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    ['detalle' => $detalle] = asignarTipoInsumoLibreta($escenario);
    actividadLibreta($escenario, $detalle, $alumno, 'Tarea 1', '10.00');

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
        ]))
        ->assertSee('100 %')
        ->assertSee('30 %')
        ->assertSee('Pond.')
        ->assertSee('>10.00</a>', false)
        ->assertSee('3.00')
        ->assertDontSee('>3.00</a>', false);
});

it('applies the school decimal mode to the pond calculation', function (ModoDecimal $modo, string $esperado) {
    $escenario = escenarioLibreta();
    $escenario['establecimiento']->update([
        'decimales_calificacion' => 2,
        'modo_decimal' => $modo,
    ]);
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Vega');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    ['detalle' => $detalle] = asignarTipoInsumoLibreta($escenario);
    actividadLibreta($escenario, $detalle, $alumno, 'Tarea 1', '7.51');

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
        ]))
        ->assertSee('>7.51</a>', false)
        ->assertSeeText($esperado);
})->with([
    'approximate' => [ModoDecimal::Aproximar, '5.26'],
    'truncate' => [ModoDecimal::Truncar, '5.25'],
]);

it('does not include a blank insumo activity in the average', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Naranjo');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    ['detalle' => $detalle] = asignarTipoInsumoLibreta($escenario);
    actividadLibreta($escenario, $detalle, $alumno, 'Tarea 1', '8.00');
    actividadLibretaSinNota($escenario, $detalle, 'Tarea en blanco');
    actividadLibretaSinNota($escenario, $detalle, 'Tarea vacía', $alumno, '');

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
        ]))
        ->assertSee('>8.00</a>', false)
        ->assertSee('5.60')
        ->assertDontSee('4.00')
        ->assertDontSee('2.80');
});

it('does not include an insumo type without notes in the weighted average', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Paredes');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    ['detalles' => $detalles] = asignarTipoInsumoLibreta($escenario, [
        ['nombre' => 'Tarea', 'porcentaje' => '50.00'],
        ['nombre' => 'Lección', 'porcentaje' => '50.00'],
    ]);
    actividadLibreta($escenario, $detalles[0], $alumno, 'Tarea 1', '10.00');
    actividadLibretaSinNota($escenario, $detalles[1], 'Lección sin nota');

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.libreta.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
        ]))
        ->assertSee('>10.00</a>', false)
        ->assertSee('7.00')
        ->assertDontSee('5.00')
        ->assertDontSee('3.50');
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
