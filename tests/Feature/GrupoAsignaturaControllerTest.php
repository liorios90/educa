<?php

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAsignaturaPropia;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoFiguraProfesional;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoGrupo;
use App\Models\EstablecimientoGrupoAlumno;
use App\Models\EstablecimientoMatricula;
use App\Models\Sys_Area;
use App\Models\User;
use App\Services\OrganizacionAcademica;

it('redirects guests to the login page', function () {
    $this->get(route('Admin.grupos-asignatura.index'))
        ->assertRedirect(route('login'));
});

it('forbids a teacher from opening floating groups', function () {
    $establecimiento = Establecimiento::factory()->create();
    $docente = assignRole(User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Docente);

    $this->actingAs($docente)
        ->get(route('Admin.grupos-asignatura.index'))
        ->assertForbidden();
});

it('allows a secretary to enroll a student in a classroom', function () {
    $curso = cursoDeOctavo();
    $secretaria = secretariaOf($curso['establecimiento']);
    $alumno = alumnoDelEstablecimiento($curso['establecimiento'], 'Herrera');
    $sesion = activeContextSession($curso['oferta'], $curso['periodo']);

    $this->actingAs($secretaria)
        ->withSession($sesion)
        ->get(route('Admin.grupos-asignatura.index'))
        ->assertOk()
        ->assertSee('Matricular alumnos')
        ->assertDontSee('Nuevo grupo flotante');

    $this->actingAs($secretaria)
        ->withSession($sesion)
        ->post(route('Admin.grupos-asignatura.matriculas.store'), [
            'alumnos' => [$alumno->id],
            'establecimiento_aula_id' => $curso['aulaA']->id,
        ])
        ->assertRedirect(route('Admin.grupos-asignatura.index'))
        ->assertSessionHas('status', 'matricula-created');

    expect(EstablecimientoMatricula::query()->where('alumno_id', $alumno->id)->value('establecimiento_aula_id'))
        ->toBe($curso['aulaA']->id);
});

it('requires a technical figure and enrolls the student in its modules', function () {
    $establecimiento = Establecimiento::factory()->create();
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta, ['numero_parciales' => 2]);
    $estructura = catalogoEstructura([
        'nivel' => 'Bachillerato',
        'subnivel' => 'Bachillerato General Unificado',
        'grado' => 'Primero de Bachillerato',
    ]);
    $grado = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'subnivel_id' => $estructura['subnivel']->id,
        'grado_id' => $estructura['grado']->id,
        'nombre' => $estructura['grado']->nombre,
    ]);
    $aula = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $grado->id,
    ]);
    $curso = compact('establecimiento', 'oferta', 'periodo', 'aula') + ['aulaA' => $aula];
    $admin = adminOf($curso['establecimiento']);
    $alumno = alumnoDelEstablecimiento($curso['establecimiento'], 'Vera');
    $figura = EstablecimientoFiguraProfesional::query()->create([
        'establecimiento_id' => $curso['establecimiento']->id,
        'establecimiento_modalidad_jornada_id' => $curso['oferta']->id,
        'nombre' => 'Informática',
    ]);
    $area = Sys_Area::factory()->create([
        'subnivel_id' => $estructura['subnivel']->id,
        'nombre' => 'Módulos técnicos',
    ]);
    $propia = EstablecimientoAsignaturaPropia::factory()->create([
        'establecimiento_id' => $curso['establecimiento']->id,
        'establecimiento_modalidad_jornada_id' => $curso['oferta']->id,
        'area_id' => $area->id,
        'figura_profesional_id' => $figura->id,
        'nombre' => 'Programación',
    ]);
    $modulo = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $curso['establecimiento']->id,
        'establecimiento_modalidad_jornada_id' => $curso['oferta']->id,
        'grado_id' => $estructura['grado']->id,
        'asignatura_id' => null,
        'asignatura_propia_id' => $propia->id,
    ]);
    $sesion = activeContextSession($curso['oferta'], $curso['periodo']);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->post(route('Admin.grupos-asignatura.matriculas.store'), [
            'alumnos' => [$alumno->id],
            'establecimiento_aula_id' => $curso['aulaA']->id,
        ])
        ->assertSessionHasErrors('figura_profesional_id');

    $this->actingAs($admin)
        ->withSession($sesion)
        ->post(route('Admin.grupos-asignatura.matriculas.store'), [
            'alumnos' => [$alumno->id],
            'establecimiento_aula_id' => $curso['aulaA']->id,
            'figura_profesional_id' => $figura->id,
        ])
        ->assertRedirect(route('Admin.grupos-asignatura.index'));

    $this->assertDatabaseHas('establecimiento_matriculas', [
        'alumno_id' => $alumno->id,
        'establecimiento_aula_id' => $curso['aulaA']->id,
        'figura_profesional_id' => $figura->id,
    ]);
    $this->assertDatabaseHas('establecimiento_alumno_asignaturas', [
        'alumno_id' => $alumno->id,
        'establecimiento_asignatura_id' => $modulo->id,
        'establecimiento_periodo_id' => $curso['periodo']->id,
    ]);
});

it('forbids a secretary from opening classrooms', function () {
    $curso = cursoDeOctavo();
    $secretaria = secretariaOf($curso['establecimiento']);

    $this->actingAs($secretaria)
        ->withSession(activeContextSession($curso['oferta'], $curso['periodo']))
        ->get(route('Admin.aulas'))
        ->assertForbidden();
});

it('enrolls students from two classrooms into one floating group', function () {
    $curso = cursoDeOctavo();
    $admin = adminOf($curso['establecimiento']);
    $delParaleloA = alumnoDelEstablecimiento($curso['establecimiento'], 'Aldaz');
    $delParaleloB = alumnoDelEstablecimiento($curso['establecimiento'], 'Benítez');
    $sesion = activeContextSession($curso['oferta'], $curso['periodo']);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->get(route('Admin.grupos-asignatura.flotantes'))
        ->assertOk()
        ->assertDontSee('Matricular alumnos')
        ->assertSee('Grupos flotantes')
        ->assertSee('Nuevo grupo flotante')
        ->assertDontSee('Nueva matrícula')
        ->assertSee('Educación Física')
        ->assertSee('Alumnos del grupo');

    $this->actingAs($admin)
        ->withSession($sesion)
        ->get(route('Admin.grupos-asignatura.index'))
        ->assertOk()
        ->assertSee('Nueva matrícula')
        ->assertSee('Buscar y seleccionar varios')
        ->assertSee('Número de matrícula')
        ->assertSee(sprintf('E%d-%d-0001', $curso['establecimiento']->id, $curso['periodo']->id))
        ->assertSee('Fecha de matrícula')
        ->assertSee('Aldaz Ana')
        ->assertSee('Benítez Ana')
        ->assertDontSee('Nuevo grupo flotante');

    $this->actingAs($admin)
        ->withSession($sesion)
        ->post(route('Admin.grupos-asignatura.matriculas.store'), [
            'alumnos' => [$delParaleloA->id],
            'establecimiento_aula_id' => $curso['aulaA']->id,
        ])
        ->assertRedirect(route('Admin.grupos-asignatura.index'));

    $this->actingAs($admin)
        ->withSession($sesion)
        ->post(route('Admin.grupos-asignatura.matriculas.store'), [
            'alumnos' => [$delParaleloB->id],
            'establecimiento_aula_id' => $curso['aulaB']->id,
        ])
        ->assertRedirect(route('Admin.grupos-asignatura.index'));

    $this->actingAs($admin)
        ->withSession($sesion)
        ->post(route('Admin.grupos-asignatura.store'), [
            'establecimiento_asignatura_id' => $curso['educacionFisica']->id,
            'nombre' => 'Grupo 2',
        ])
        ->assertRedirect(route('Admin.grupos-asignatura.flotantes'))
        ->assertSessionHas('status', 'grupo-created');

    $grupo = EstablecimientoGrupo::query()->where('nombre', 'Grupo 2')->first();

    $this->actingAs($admin)
        ->withSession($sesion)
        ->put(route('Admin.grupos-asignatura.alumnos.update', $grupo), [
            'alumnos' => [$delParaleloA->id, $delParaleloB->id],
        ])
        ->assertRedirect(route('Admin.grupos-asignatura.flotantes'));

    expect(EstablecimientoMatricula::query()->where('alumno_id', $delParaleloB->id)->value('establecimiento_aula_id'))->toBe($curso['aulaB']->id)
        ->and(EstablecimientoGrupoAlumno::query()->where('establecimiento_grupo_id', $grupo->id)->count())->toBe(2)
        ->and($curso['educacionFisica']->fresh()->es_flotante)->toBeTrue();
});

it('returns 404 when the group belongs to another school', function () {
    $curso = cursoDeOctavo();
    $grupo = app(OrganizacionAcademica::class)->crearGrupo($curso['periodo'], $curso['educacionFisica'], 'Grupo 1');
    $otro = Establecimiento::factory()->create();
    $admin = adminOf($otro);
    $oferta = ofertaDe($otro);
    $periodo = periodoActivoDe($oferta);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->put(route('Admin.grupos-asignatura.alumnos.update', $grupo), [
            'alumnos' => [],
        ])
        ->assertNotFound();
});

it('enrolls several students in one classroom in a single request', function () {
    $curso = cursoDeOctavo();
    $admin = adminOf($curso['establecimiento']);
    $primero = alumnoDelEstablecimiento($curso['establecimiento'], 'Cárdenas');
    $segundo = alumnoDelEstablecimiento($curso['establecimiento'], 'Dávila');
    $sesion = activeContextSession($curso['oferta'], $curso['periodo']);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->post(route('Admin.grupos-asignatura.matriculas.store'), [
            'alumnos' => [$primero->id, $segundo->id],
            'establecimiento_aula_id' => $curso['aulaA']->id,
        ])
        ->assertRedirect(route('Admin.grupos-asignatura.index'))
        ->assertSessionHas('status', 'matricula-created')
        ->assertSessionHas('matriculas_creadas', 2);

    expect(EstablecimientoMatricula::query()->where('alumno_id', $primero->id)->value('establecimiento_aula_id'))->toBe($curso['aulaA']->id)
        ->and(EstablecimientoMatricula::query()->where('alumno_id', $segundo->id)->value('establecimiento_aula_id'))->toBe($curso['aulaA']->id)
        ->and(EstablecimientoMatricula::query()->where('alumno_id', $primero->id)->value('numero'))->toBe(sprintf('E%d-%d-0001', $curso['establecimiento']->id, $curso['periodo']->id))
        ->and(EstablecimientoMatricula::query()->where('alumno_id', $segundo->id)->value('numero'))->toBe(sprintf('E%d-%d-0002', $curso['establecimiento']->id, $curso['periodo']->id));
});

it('lists enrolled students on each classroom card', function () {
    $curso = cursoDeOctavo();
    $admin = adminOf($curso['establecimiento']);
    $primero = alumnoDelEstablecimiento($curso['establecimiento'], 'Cárdenas');
    $segundo = alumnoDelEstablecimiento($curso['establecimiento'], 'Dávila');
    $sesion = activeContextSession($curso['oferta'], $curso['periodo']);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->post(route('Admin.grupos-asignatura.matriculas.store'), [
            'alumnos' => [$primero->id, $segundo->id],
            'establecimiento_aula_id' => $curso['aulaA']->id,
        ]);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->get(route('Admin.grupos-asignatura.index'))
        ->assertOk()
        ->assertSee('Octavo de básica A')
        ->assertSee('2 alumnos')
        ->assertSee('Alumnos')
        ->assertSee('Quitar')
        ->assertSee('Cárdenas Ana')
        ->assertSee('Dávila Ana')
        ->assertSee(sprintf('E%d-%d-0001', $curso['establecimiento']->id, $curso['periodo']->id))
        ->assertSee(sprintf('E%d-%d-0002', $curso['establecimiento']->id, $curso['periodo']->id));
});

it('lists classrooms in academic order', function () {
    $establecimiento = Establecimiento::factory()->create();
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $admin = adminOf($establecimiento);
    $primero = catalogoEstructura([
        'nivel' => 'Educación General Básica',
        'subnivel' => 'Preparatoria',
        'grado' => 'Primero de EGB',
    ]);
    $decimo = catalogoEstructura([
        'nivel' => 'Educación General Básica Superior',
        'subnivel' => 'Básica Superior',
        'grado' => 'Décimo de EGB',
    ]);
    $gradoPrimero = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'subnivel_id' => $primero['grado']->subnivel_id,
        'grado_id' => $primero['grado']->id,
        'nombre' => 'Primero de EGB',
    ]);
    $gradoDecimo = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'subnivel_id' => $decimo['grado']->subnivel_id,
        'grado_id' => $decimo['grado']->id,
        'nombre' => 'Décimo de EGB',
    ]);

    EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $gradoDecimo->id,
        'paralelo' => 'A',
    ]);
    EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $gradoPrimero->id,
        'paralelo' => 'A',
    ]);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->get(route('Admin.grupos-asignatura.index'))
        ->assertOk()
        ->assertSeeInOrder([
            'Primero de EGB A',
            'Décimo de EGB A',
        ]);
});

it('asks for students and a classroom when the enrollment form is empty', function () {
    $curso = cursoDeOctavo();
    $admin = adminOf($curso['establecimiento']);

    $this->actingAs($admin)
        ->withSession(activeContextSession($curso['oferta'], $curso['periodo']))
        ->from(route('Admin.grupos-asignatura.index'))
        ->post(route('Admin.grupos-asignatura.matriculas.store'), [])
        ->assertRedirect(route('Admin.grupos-asignatura.index'))
        ->assertSessionHasErrors([
            'establecimiento_aula_id' => 'Selecciona el paralelo.',
            'alumnos' => 'Selecciona al menos un alumno.',
        ]);
});

it('returns 404 when an enrolled student belongs to another school', function () {
    $curso = cursoDeOctavo();
    $admin = adminOf($curso['establecimiento']);
    $ajeno = alumnoDelEstablecimiento(Establecimiento::factory()->create(), 'Externo');

    $this->actingAs($admin)
        ->withSession(activeContextSession($curso['oferta'], $curso['periodo']))
        ->post(route('Admin.grupos-asignatura.matriculas.store'), [
            'alumnos' => [$ajeno->id],
            'establecimiento_aula_id' => $curso['aulaA']->id,
        ])
        ->assertNotFound();
});

it('rejects a student already enrolled in another classroom', function () {
    $curso = cursoDeOctavo();
    $admin = adminOf($curso['establecimiento']);
    $alumno = alumnoDelEstablecimiento($curso['establecimiento'], 'Cárdenas');
    $sesion = activeContextSession($curso['oferta'], $curso['periodo']);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->post(route('Admin.grupos-asignatura.matriculas.store'), [
            'alumnos' => [$alumno->id],
            'establecimiento_aula_id' => $curso['aulaA']->id,
        ]);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->from(route('Admin.grupos-asignatura.index'))
        ->post(route('Admin.grupos-asignatura.matriculas.store'), [
            'alumnos' => [$alumno->id],
            'establecimiento_aula_id' => $curso['aulaB']->id,
        ])
        ->assertRedirect(route('Admin.grupos-asignatura.index'))
        ->assertSessionHasErrors([
            'alumno_id' => 'El alumno ya está matriculado en un paralelo de este periodo.',
        ]);
});

it('removes a student from the classroom', function () {
    $curso = cursoDeOctavo();
    $admin = adminOf($curso['establecimiento']);
    $alumno = alumnoDelEstablecimiento($curso['establecimiento'], 'Cárdenas');
    $sesion = activeContextSession($curso['oferta'], $curso['periodo']);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->post(route('Admin.grupos-asignatura.matriculas.store'), [
            'alumnos' => [$alumno->id],
            'establecimiento_aula_id' => $curso['aulaA']->id,
        ]);

    $matricula = EstablecimientoMatricula::query()->where('alumno_id', $alumno->id)->first();

    $this->actingAs($admin)
        ->withSession($sesion)
        ->delete(route('Admin.grupos-asignatura.matriculas.destroy', $matricula))
        ->assertRedirect(route('Admin.grupos-asignatura.index'))
        ->assertSessionHas('status', 'matricula-deleted');

    expect(EstablecimientoMatricula::query()->where('alumno_id', $alumno->id)->exists())->toBeFalse();
});

it('returns 404 when the enrollment belongs to another school', function () {
    $curso = cursoDeOctavo();
    $matricula = app(OrganizacionAcademica::class)->matricular(
        $curso['aulaA'],
        alumnoDelEstablecimiento($curso['establecimiento'], 'Cárdenas'),
    );
    $otro = Establecimiento::factory()->create();
    $admin = adminOf($otro);
    $oferta = ofertaDe($otro);
    $periodo = periodoActivoDe($oferta);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->delete(route('Admin.grupos-asignatura.matriculas.destroy', $matricula))
        ->assertNotFound();
});

it('rejects removing a student who already has grades', function () {
    $curso = cursoDeOctavo();
    $admin = adminOf($curso['establecimiento']);
    $alumno = alumnoDelEstablecimiento($curso['establecimiento'], 'Cárdenas');
    $organizacion = app(OrganizacionAcademica::class);
    $matricula = $organizacion->matricular($curso['aulaA'], $alumno);
    $organizacion->registrarCalificacion($alumno, $curso['periodo'], $curso['matematica'], 1, 0, 8.5);
    $sesion = activeContextSession($curso['oferta'], $curso['periodo']);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->from(route('Admin.grupos-asignatura.index'))
        ->delete(route('Admin.grupos-asignatura.matriculas.destroy', $matricula), [
            'establecimiento_aula_id' => $curso['aulaA']->id,
        ])
        ->assertRedirect(route('Admin.grupos-asignatura.index'))
        ->assertSessionHasErrors([
            'alumno_id' => 'No puedes quitar a un alumno que ya tiene notas en este periodo.',
        ]);

    expect(EstablecimientoMatricula::query()->where('id', $matricula->id)->exists())->toBeTrue();
});

it('creates a floating group with the selected students', function () {
    $curso = cursoDeOctavo();
    $admin = adminOf($curso['establecimiento']);
    $primero = alumnoDelEstablecimiento($curso['establecimiento'], 'Aldaz');
    $segundo = alumnoDelEstablecimiento($curso['establecimiento'], 'Benítez');
    $organizacion = app(OrganizacionAcademica::class);
    $organizacion->matricular($curso['aulaA'], $primero);
    $organizacion->matricular($curso['aulaB'], $segundo);
    $sesion = activeContextSession($curso['oferta'], $curso['periodo']);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->get(route('Admin.grupos-asignatura.flotantes'))
        ->assertSee('Alumnos del grupo')
        ->assertSee('Buscar y seleccionar varios')
        ->assertSee('Solo aparecen alumnos ya matriculados en un paralelo de este grado.')
        ->assertSee('Aldaz Ana')
        ->assertSee('Benítez Ana');

    $this->actingAs($admin)
        ->withSession($sesion)
        ->post(route('Admin.grupos-asignatura.store'), [
            'establecimiento_asignatura_id' => $curso['educacionFisica']->id,
            'nombre' => 'Grupo 2',
            'alumnos' => [$primero->id, $segundo->id],
        ])
        ->assertRedirect(route('Admin.grupos-asignatura.flotantes'))
        ->assertSessionHas('status', 'grupo-created');

    $grupo = EstablecimientoGrupo::query()->where('nombre', 'Grupo 2')->first();

    expect($grupo)->not->toBeNull();
    expect(
        EstablecimientoGrupoAlumno::query()
            ->where('establecimiento_grupo_id', $grupo->id)
            ->pluck('alumno_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all()
    )->toEqualCanonicalizing([$primero->id, $segundo->id]);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->get(route('Admin.grupos-asignatura.flotantes'))
        ->assertSee('Grupo 2')
        ->assertSee('Eliminar grupo')
        ->assertSee('Octavo de básica A · 1 alumno')
        ->assertSee('Octavo de básica B · 1 alumno');
});

it('asks for the group name', function () {
    $curso = cursoDeOctavo();
    $admin = adminOf($curso['establecimiento']);

    $this->actingAs($admin)
        ->withSession(activeContextSession($curso['oferta'], $curso['periodo']))
        ->post(route('Admin.grupos-asignatura.store'), [
            'establecimiento_asignatura_id' => $curso['educacionFisica']->id,
            'nombre' => '',
        ])
        ->assertSessionHasErrors([
            'nombre' => 'Escribe el nombre del grupo.',
        ]);
});
