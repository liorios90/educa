<?php

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

it('redirects guests from the family gradebook to the login page', function () {
    $this->get(route('notas.index'))
        ->assertRedirect(route('login'));
});

it('forbids other roles from opening the family gradebook', function (User $user) {
    $this->actingAs($user)
        ->get(route('notas.index'))
        ->assertForbidden();
})->with([
    'administrator' => fn () => adminOf(Establecimiento::factory()->create()),
    'secretary' => fn () => secretariaOf(Establecimiento::factory()->create()),
    'teacher' => fn () => assignRole(User::factory()->create([
        'establecimiento_id' => Establecimiento::factory()->create()->id,
    ]), Role::Docente),
]);

it('lets a student see only their own grades', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Herrera');
    $companero = alumnoDelEstablecimiento($escenario['establecimiento'], 'Castro');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    matricularAlumnoLibreta($escenario['aula'], $companero);
    ['detalle' => $detalle] = asignarTipoInsumoLibreta($escenario);
    actividadLibreta($escenario, $detalle, $alumno, 'Tarea 1', '8.00');
    actividadLibreta($escenario, $detalle, $companero, 'Tarea compañero', '4.00');
    $cuenta = cuentaAlumnoDe($alumno, $escenario['establecimiento']);

    $this->actingAs($cuenta)
        ->get(route('notas.index'))
        ->assertOk()
        ->assertSee('Notas')
        ->assertSee('Herrera Ana')
        ->assertSee('Matemática')
        ->assertSee('8.00')
        ->assertSee('5.60')
        ->assertDontSee('Castro Ana')
        ->assertDontSee('4.00');
});

it('does not include a blank insumo in the student gradebook average', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Naranjo');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    ['detalle' => $detalle] = asignarTipoInsumoLibreta($escenario);
    actividadLibreta($escenario, $detalle, $alumno, 'Tarea 1', '8.00');
    actividadLibretaSinNota($escenario, $detalle, 'Tarea en blanco');
    $cuenta = cuentaAlumnoDe($alumno, $escenario['establecimiento']);

    $this->actingAs($cuenta)
        ->get(route('notas.index'))
        ->assertSee('>8.00</a>', false)
        ->assertSee('5.60')
        ->assertDontSee('4.00');
});

it('lets a parent see the grades of a single linked child', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Espinoza');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    ['detalle' => $detalle] = asignarTipoInsumoLibreta($escenario);
    actividadLibreta($escenario, $detalle, $alumno, 'Tarea 1', '10.00');
    $padre = cuentaPadreDe($escenario['establecimiento'], $alumno);

    $this->actingAs($padre)
        ->get(route('notas.index'))
        ->assertOk()
        ->assertSee('Espinoza Ana')
        ->assertSee('>10.00</a>', false)
        ->assertSee('7.00')
        ->assertDontSee('Selecciona el hijo cuyas notas quieres ver')
        ->assertDontSee('Cambiar de hijo');
});

it('does not include a blank insumo in the parent gradebook average', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Paredes');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    ['detalles' => $detalles] = asignarTipoInsumoLibreta($escenario, [
        ['nombre' => 'Tarea', 'porcentaje' => '50.00'],
        ['nombre' => 'Lección', 'porcentaje' => '50.00'],
    ]);
    actividadLibreta($escenario, $detalles[0], $alumno, 'Tarea 1', '10.00');
    actividadLibretaSinNota($escenario, $detalles[1], 'Lección sin nota', $alumno, '');
    $padre = cuentaPadreDe($escenario['establecimiento'], $alumno);

    $this->actingAs($padre)
        ->get(route('notas.index'))
        ->assertSee('>10.00</a>', false)
        ->assertSee('7.00')
        ->assertDontSee('5.00')
        ->assertDontSee('3.50');
});

it('lets a parent choose among several children', function () {
    $escenario = escenarioLibreta();
    $primero = alumnoDelEstablecimiento($escenario['establecimiento'], 'Aldaz');
    $segundo = alumnoDelEstablecimiento($escenario['establecimiento'], 'Benítez');
    matricularAlumnoLibreta($escenario['aula'], $primero);
    matricularAlumnoLibreta($escenario['aula'], $segundo);
    ['detalle' => $detalle] = asignarTipoInsumoLibreta($escenario);
    actividadLibreta($escenario, $detalle, $primero, 'Tarea 1', '9.00');
    actividadLibreta($escenario, $detalle, $segundo, 'Tarea 2', '6.00');
    $padre = cuentaPadreDe($escenario['establecimiento'], $primero, $segundo);

    $this->actingAs($padre)
        ->get(route('notas.index'))
        ->assertOk()
        ->assertSee('Selecciona el hijo cuyas notas quieres ver')
        ->assertSee('Aldaz Ana')
        ->assertSee('Benítez Ana')
        ->assertSee('Ver notas')
        ->assertSee(route('notas.index', ['alumno_id' => $primero->id]), false)
        ->assertSee(route('notas.index', ['alumno_id' => $segundo->id]), false)
        ->assertDontSee('>9.00</a>', false)
        ->assertDontSee('>6.00</a>', false);

    $this->actingAs($padre)
        ->get(route('notas.index', ['alumno_id' => $primero->id]))
        ->assertOk()
        ->assertSee('Cambiar de hijo')
        ->assertSee('Consultando')
        ->assertSee('>9.00</a>', false)
        ->assertDontSee('>6.00</a>', false);

    $this->actingAs($padre)
        ->get(route('notas.index', ['alumno_id' => $segundo->id]))
        ->assertOk()
        ->assertSee('>6.00</a>', false)
        ->assertDontSee('>9.00</a>', false);
});

it('does not show another student when a parent asks for a child that is not theirs', function () {
    $escenario = escenarioLibreta();
    $propio = alumnoDelEstablecimiento($escenario['establecimiento'], 'Mora');
    $ajeno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Paz');
    matricularAlumnoLibreta($escenario['aula'], $ajeno);
    $padre = cuentaPadreDe($escenario['establecimiento'], $propio);

    $this->actingAs($padre)
        ->get(route('notas.index', ['alumno_id' => $ajeno->id]))
        ->assertNotFound();
});

it('does not show another student when a student asks for a classmate', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Vega');
    $companero = alumnoDelEstablecimiento($escenario['establecimiento'], 'Luna');
    matricularAlumnoLibreta($escenario['aula'], $companero);
    $cuenta = cuentaAlumnoDe($alumno, $escenario['establecimiento']);

    $this->actingAs($cuenta)
        ->get(route('notas.index', ['alumno_id' => $companero->id]))
        ->assertNotFound();
});

it('does not show a child from another school to a parent', function () {
    $escenario = escenarioLibreta();
    $ajeno = escenarioLibreta(['grado' => '9no Básica']);
    $hijo = alumnoDelEstablecimiento($escenario['establecimiento'], 'Salas');
    $otro = alumnoDelEstablecimiento($ajeno['establecimiento'], 'Torres');
    matricularAlumnoLibreta($ajeno['aula'], $otro);
    $padre = cuentaPadreDe($escenario['establecimiento'], $hijo);

    $this->actingAs($padre)
        ->get(route('notas.index', ['alumno_id' => $otro->id]))
        ->assertNotFound();
});

it('explains when the student has no active enrollment', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Naranjo');
    $cuenta = cuentaAlumnoDe($alumno, $escenario['establecimiento']);

    $this->actingAs($cuenta)
        ->get(route('notas.index'))
        ->assertOk()
        ->assertSee('Naranjo Ana no tiene una matrícula en un periodo activo.');
});

it('escapes a dangerous student name on the family gradebook', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], '<script>alert(1)</script>');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    $padre = cuentaPadreDe($escenario['establecimiento'], $alumno);

    $this->actingAs($padre)
        ->get(route('notas.index'))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});

it('shows the grades link to parents and students', function () {
    $this->seed(NavigationSeeder::class);
    $establecimiento = Establecimiento::factory()->create();
    $alumno = alumnoDelEstablecimiento($establecimiento, 'Herrera');
    $padre = cuentaPadreDe($establecimiento, $alumno);
    $cuentaAlumno = cuentaAlumnoDe($alumno, $establecimiento);

    $this->actingAs($padre)
        ->get(route('dashboard'))
        ->assertSee('Notas')
        ->assertSee(route('notas.index'), false);

    $this->actingAs($cuentaAlumno)
        ->get(route('dashboard'))
        ->assertSee('Notas')
        ->assertSee(route('notas.index'), false);
});
