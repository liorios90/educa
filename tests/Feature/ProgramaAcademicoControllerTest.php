<?php

use App\Enums\EscalaCalificacion;
use App\Enums\Role;
use App\Enums\TipoPrograma;
use App\Models\Establecimiento;
use App\Models\EstablecimientoPrograma;
use App\Models\User;
use App\Services\OrganizacionAcademica;

it('redirects guests to the login page', function () {
    $this->get(route('Admin.programas.index'))
        ->assertRedirect(route('login'));
});

it('forbids a teacher from opening programs', function () {
    $curso = cursoDeOctavo();
    $docente = assignRole(User::factory()->create([
        'establecimiento_id' => $curso['establecimiento']->id,
    ]), Role::Docente);

    $this->actingAs($docente)
        ->get(route('Admin.programas.index'))
        ->assertForbidden();
});

it('allows a secretary to create a program', function () {
    $curso = cursoDeOctavo();
    $secretaria = secretariaOf($curso['establecimiento']);
    $sesion = activeContextSession($curso['oferta'], $curso['periodo']);

    $this->actingAs($secretaria)
        ->withSession($sesion)
        ->get(route('Admin.programas.index'))
        ->assertOk()
        ->assertSee('Programas');

    $this->actingAs($secretaria)
        ->withSession($sesion)
        ->post(route('Admin.programas.store'), [
            'tipo' => TipoPrograma::BachilleratoInternacional->value,
            'nombre' => 'Bachillerato Internacional',
        ])
        ->assertRedirect(route('Admin.programas.index'))
        ->assertSessionHas('status', 'programa-created');

    expect(EstablecimientoPrograma::query()->where('nombre', 'Bachillerato Internacional')->exists())->toBeTrue();
});

it('enrolls one student in one international baccalaureate subject', function () {
    $curso = cursoDeOctavo();
    $admin = adminOf($curso['establecimiento']);
    $delPrograma = alumnoDelEstablecimiento($curso['establecimiento'], 'Flores');
    $companero = alumnoDelEstablecimiento($curso['establecimiento'], 'Guerra');
    $organizacion = app(OrganizacionAcademica::class);
    $organizacion->matricular($curso['aulaA'], $delPrograma);
    $organizacion->matricular($curso['aulaB'], $companero);
    $sesion = activeContextSession($curso['oferta'], $curso['periodo']);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->post(route('Admin.programas.store'), [
            'tipo' => TipoPrograma::BachilleratoInternacional->value,
            'nombre' => 'Bachillerato Internacional',
        ])
        ->assertRedirect(route('Admin.programas.index'))
        ->assertSessionHas('status', 'programa-created');

    $programa = EstablecimientoPrograma::query()->where('nombre', 'Bachillerato Internacional')->first();

    $this->actingAs($admin)
        ->withSession($sesion)
        ->put(route('Admin.programas.alumnos.update', $programa), [
            'alumnos' => [$delPrograma->id],
        ])
        ->assertRedirect(route('Admin.programas.index'));

    $this->actingAs($admin)
        ->withSession($sesion)
        ->put(route('Admin.programas.asignaturas.update', $programa), [
            'asignaturas' => [$curso['bachillerato']->id],
        ])
        ->assertRedirect(route('Admin.programas.index'));

    $this->actingAs($admin)
        ->withSession($sesion)
        ->put(route('Admin.programas.inscripciones.update', [$programa, $curso['bachillerato']]), [
            'alumnos' => [$delPrograma->id],
        ])
        ->assertRedirect(route('Admin.programas.index'));

    $this->actingAs($admin)
        ->withSession($sesion)
        ->get(route('Admin.programas.index'))
        ->assertOk()
        ->assertSee('Matemáticas AA NS')
        ->assertSee('Flores');

    $asignatura = $curso['bachillerato']->fresh();

    expect($programa->alumnos()->pluck('alumnos.id')->all())->toBe([$delPrograma->id])
        ->and($asignatura->escala)->toBe(EscalaCalificacion::BachilleratoInternacional)
        ->and($asignatura->aparece_en_libreta)->toBeFalse()
        ->and($asignatura->inscripciones()->where('alumno_id', $delPrograma->id)->exists())->toBeTrue()
        ->and($asignatura->inscripciones()->where('alumno_id', $companero->id)->exists())->toBeFalse();
});

it('creates several programs and lists the student chip picker', function () {
    $curso = cursoDeOctavo();
    $admin = adminOf($curso['establecimiento']);
    $alumno = alumnoDelEstablecimiento($curso['establecimiento'], 'Flores');
    app(OrganizacionAcademica::class)->matricular($curso['aulaA'], $alumno);
    $sesion = activeContextSession($curso['oferta'], $curso['periodo']);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->post(route('Admin.programas.store'), [
            'tipo' => TipoPrograma::BachilleratoInternacional->value,
            'nombre' => 'Bachillerato Internacional',
        ])
        ->assertRedirect(route('Admin.programas.index'));

    $this->actingAs($admin)
        ->withSession($sesion)
        ->post(route('Admin.programas.store'), [
            'tipo' => TipoPrograma::BachilleratoInternacional->value,
            'nombre' => 'Diploma IB',
        ])
        ->assertRedirect(route('Admin.programas.index'))
        ->assertSessionHas('status', 'programa-created');

    $this->actingAs($admin)
        ->withSession($sesion)
        ->get(route('Admin.programas.index'))
        ->assertOk()
        ->assertSee('Nuevo programa')
        ->assertSee('Bachillerato Internacional')
        ->assertSee('Diploma IB')
        ->assertSee('Alumnos del programa')
        ->assertSee('Buscar y seleccionar varios')
        ->assertSee('Flores Ana');

    expect(EstablecimientoPrograma::query()->where('establecimiento_periodo_id', $curso['periodo']->id)->count())->toBe(2);
});

it('updates the name of a program', function () {
    $curso = cursoDeOctavo();
    $admin = adminOf($curso['establecimiento']);
    $programa = app(OrganizacionAcademica::class)->crearPrograma(
        $curso['periodo'],
        TipoPrograma::BachilleratoInternacional,
        'Bachillerato Internacional',
    );
    $sesion = activeContextSession($curso['oferta'], $curso['periodo']);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->put(route('Admin.programas.update', $programa), [
            'tipo' => TipoPrograma::BachilleratoInternacional->value,
            'nombre' => 'Diploma IB',
        ])
        ->assertRedirect(route('Admin.programas.index'))
        ->assertSessionHas('status', 'programa-updated');

    expect($programa->fresh()->nombre)->toBe('Diploma IB');
});

it('deletes a program', function () {
    $curso = cursoDeOctavo();
    $admin = adminOf($curso['establecimiento']);
    $programa = app(OrganizacionAcademica::class)->crearPrograma(
        $curso['periodo'],
        TipoPrograma::BachilleratoInternacional,
        'Bachillerato Internacional',
    );
    $sesion = activeContextSession($curso['oferta'], $curso['periodo']);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->delete(route('Admin.programas.destroy', $programa))
        ->assertRedirect(route('Admin.programas.index'))
        ->assertSessionHas('status', 'programa-deleted');

    expect($programa->fresh())->toBeNull();
});

it('rejects a repeated program name', function () {
    $curso = cursoDeOctavo();
    $admin = adminOf($curso['establecimiento']);
    app(OrganizacionAcademica::class)->crearPrograma(
        $curso['periodo'],
        TipoPrograma::BachilleratoInternacional,
        'Bachillerato Internacional',
    );
    $sesion = activeContextSession($curso['oferta'], $curso['periodo']);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->from(route('Admin.programas.index'))
        ->post(route('Admin.programas.store'), [
            'tipo' => TipoPrograma::BachilleratoInternacional->value,
            'nombre' => 'Bachillerato Internacional',
        ])
        ->assertRedirect(route('Admin.programas.index'))
        ->assertSessionHasErrors(['nombre' => 'Ya existe un programa con ese nombre en este periodo.']);
});

it('returns 404 when the program belongs to another school', function () {
    $curso = cursoDeOctavo();
    $programa = app(OrganizacionAcademica::class)->crearPrograma(
        $curso['periodo'],
        TipoPrograma::BachilleratoInternacional,
        'Bachillerato Internacional',
    );
    $otro = Establecimiento::factory()->create();
    $admin = adminOf($otro);
    $oferta = ofertaDe($otro);
    $periodo = periodoActivoDe($oferta);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->put(route('Admin.programas.update', $programa), [
            'tipo' => TipoPrograma::BachilleratoInternacional->value,
            'nombre' => 'Diploma IB',
        ])
        ->assertNotFound();
});

it('returns 404 when deleting a program of another school', function () {
    $curso = cursoDeOctavo();
    $programa = app(OrganizacionAcademica::class)->crearPrograma(
        $curso['periodo'],
        TipoPrograma::BachilleratoInternacional,
        'Bachillerato Internacional',
    );
    $otro = Establecimiento::factory()->create();
    $admin = adminOf($otro);
    $oferta = ofertaDe($otro);
    $periodo = periodoActivoDe($oferta);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->delete(route('Admin.programas.destroy', $programa))
        ->assertNotFound();

    expect($programa->fresh())->not->toBeNull();
});
