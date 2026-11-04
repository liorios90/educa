<?php

use App\Enums\Role;
use App\Enums\TipoCierreEvaluacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoCierreActividad;
use App\Models\Sys_Grado;
use App\Models\User;

it('redirects guests to the login page', function () {
    $this->get(route('secretaria.certificado-promocion.index'))
        ->assertRedirect(route('login'));
});

it('forbids roles other than secretary from opening the promotion certificates', function (User $user) {
    $this->actingAs($user)
        ->get(route('secretaria.certificado-promocion.index'))
        ->assertForbidden();
})->with([
    'administrator' => fn () => adminOf(Establecimiento::factory()->create()),
    'teacher' => fn () => assignRole(User::factory()->create([
        'establecimiento_id' => Establecimiento::factory()->create()->id,
    ]), Role::Docente),
]);

it('lists classrooms before generating promotion certificates', function () {
    $escenario = escenarioLibreta();

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.certificado-promocion.index'))
        ->assertSee('Certificado de promoción')
        ->assertSee('8vo Básica A')
        ->assertSee('8vo Básica B')
        ->assertDontSee('Matemática');
});

it('certifies promotion to the next grade when every subject reaches 7.00', function () {
    $escenario = escenarioLibreta();
    $escenario['aula']->load('establecimientoGrado.grado');
    Sys_Grado::factory()->create([
        'subnivel_id' => $escenario['aula']->establecimientoGrado->grado->subnivel_id,
        'nombre' => '9no de básica',
    ]);
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Herrera');
    $alumno->persona->update(['identificacion' => '1712345678']);
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
        ->get(route('secretaria.certificado-promocion.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
        ]))
        ->assertSee('Certificado de promoción')
        ->assertSee('Herrera Ana')
        ->assertSee('1712345678')
        ->assertSee('8vo Básica')
        ->assertSee('Matemática')
        ->assertSee('8.60')
        ->assertSee('Alcanza los aprendizajes requeridos')
        ->assertSee('Es promovido/a a 9no de básica')
        ->assertDontSee('No reúne los requisitos de promoción');
});

it('does not promote a student with an annual subject average below 7.00', function () {
    $escenario = escenarioLibreta();
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Castro');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    ['detalle' => $detalle] = asignarTipoInsumoLibreta($escenario);
    actividadLibreta($escenario, $detalle, $alumno, 'Tarea 1', '5.00');

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.certificado-promocion.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
        ]))
        ->assertSee('Castro Ana')
        ->assertSee('5.00')
        ->assertSee('No reúne los requisitos de promoción previstos en el Art. 197')
        ->assertDontSee('Es promovido/a');
});

it('does not show a classroom from another school', function () {
    $escenario = escenarioLibreta();
    $ajeno = escenarioLibreta(['grado' => '9no Básica']);

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.certificado-promocion.index'))
        ->assertSee('8vo Básica A')
        ->assertDontSee('9no Básica');

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.certificado-promocion.index', [
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
        ->get(route('secretaria.certificado-promocion.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
        ]))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});
