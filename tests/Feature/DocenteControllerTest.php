<?php

use App\Enums\Role;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\Persona;
use App\Models\Sys_Asignatura;
use App\Models\User;

describe('index', function () {
    it('lists docentes of the establishment with their specialty', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        [$oferta, $elemental, $media] = especialidadesDeUnArea($establecimiento);
        $docente = docenteDe($establecimiento, 'Mora', 'Luis');
        $docente->especialidades()->attach([$elemental->id, $media->id]);
        empleadoDe($establecimiento, Role::Secretaria, 'Perez', 'Ana');

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->get(route('Admin.docentes'))
            ->assertOk()
            ->assertSee('Docentes')
            ->assertSee('Mora')
            ->assertSee('Luis')
            ->assertSee('Básica Elemental · Matemática')
            ->assertSee('Básica Media · Matemática')
            ->assertSee('Áreas de especialización')
            ->assertSee(route('Admin.docentes.areas', $docente), false)
            ->assertDontSee('Perez')
            ->assertDontSee('Ana')
            ->assertDontSee('Subnivel: Básica Elemental')
            ->assertDontSee('Guardar áreas')
            ->assertDontSee('Matemática · Matemática')
            ->assertDontSee('Matemática - Matemática');
    });

    it('does not list docentes from another establishment', function () {
        $admin = adminOf(Establecimiento::factory()->create());
        docenteDe(Establecimiento::factory()->create(), 'Colegio', 'Otro');

        $this->actingAs($admin)
            ->get(route('Admin.docentes'))
            ->assertOk()
            ->assertDontSee('Colegio')
            ->assertDontSee('Otro')
            ->assertSee('Aún no hay empleados con el rol Docente.');
    });

    it('asks for the session malla before areas can be chosen', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $docente = docenteDe($establecimiento, 'Mora', 'Luis');

        $this->actingAs($admin)
            ->get(route('Admin.docentes.areas', $docente))
            ->assertOk()
            ->assertSee('Define la malla de asignaturas de esta jornada antes de registrar la especialidad.')
            ->assertDontSee('Guardar áreas');
    });

    it('lists each area under its subnivel and checks the saved ones', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        [$oferta, $elemental, $media] = especialidadesDeUnArea($establecimiento);
        $docente = docenteDe($establecimiento, 'Mora', 'Luis');
        $docente->especialidades()->attach($elemental->id);

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->get(route('Admin.docentes.areas', $docente))
            ->assertOk()
            ->assertSee('Áreas de especialización')
            ->assertSee('Subnivel: Básica Elemental')
            ->assertSee('Subnivel: Básica Media')
            ->assertSee('name="areas[]" value="'.$elemental->area_id.'" checked', false)
            ->assertSee('name="areas[]" value="'.$media->area_id.'"', false)
            ->assertDontSee('name="areas[]" value="'.$media->area_id.'" checked', false)
            ->assertDontSee('Matemática · Matemática')
            ->assertDontSee('Matemática - Matemática');
    });

    it('forbids systems users from opening docentes', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get(route('Admin.docentes'))
            ->assertForbidden();
    });

    it('redirects guests from docentes to login', function () {
        $this->get(route('Admin.docentes'))
            ->assertRedirect(route('login'));
    });

    it('redirects guests from areas to login', function () {
        $docente = docenteDe(Establecimiento::factory()->create(), 'Mora', 'Luis');

        $this->get(route('Admin.docentes.areas', $docente))
            ->assertRedirect(route('login'));
    });

    it('forbids administrators without an establishment', function () {
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.docentes'))
            ->assertForbidden();
    });

    it('escapes the docente name', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        docenteDe($establecimiento, "<script>alert('xss')</script>", 'Luis');

        $this->actingAs($admin)
            ->get(route('Admin.docentes'))
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });
});

describe('update', function () {
    it('saves every subject of each checked area', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        [$oferta, $elemental, $media] = especialidadesDeUnArea($establecimiento);
        $geometria = Sys_Asignatura::factory()->create([
            'nombre' => 'Geometría',
            'area_id' => $elemental->area_id,
        ]);
        EstablecimientoAsignatura::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'asignatura_id' => $geometria->id,
        ]);
        $docente = docenteDe($establecimiento, 'Mora', 'Luis');
        $docente->especialidades()->attach($elemental->id);

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->patch(route('Admin.docentes.update', $docente), [
                'areas' => [$elemental->area_id, $media->area_id],
            ])
            ->assertRedirect(route('Admin.docentes'))
            ->assertSessionHas('status', 'docente-especialidad-updated');

        expect($docente->especialidades()->pluck('sys_asignaturas.id')->all())
            ->toBe([$geometria->id, $elemental->id, $media->id]);
    });

    it('rejects a docente without an area', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        [$oferta] = especialidadEnJornada($establecimiento, 'Matemática');
        $docente = docenteDe($establecimiento, 'Mora', 'Luis');

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->from(route('Admin.docentes.areas', $docente))
            ->patch(route('Admin.docentes.update', $docente), [])
            ->assertRedirect(route('Admin.docentes.areas', $docente))
            ->assertSessionHasErrors([
                'areas' => 'Marca las áreas de especialización del docente.',
            ]);

        expect($docente->especialidades)->toHaveCount(0);
    });

    it('rejects an area when the session has no malla', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $docente = docenteDe($establecimiento, 'Mora', 'Luis');
        $asignatura = Sys_Asignatura::factory()->create();

        $this->actingAs($admin)
            ->from(route('Admin.docentes.areas', $docente))
            ->patch(route('Admin.docentes.update', $docente), [
                'areas' => [$asignatura->area_id],
            ])
            ->assertRedirect(route('Admin.docentes.areas', $docente))
            ->assertSessionHasErrors([
                'areas' => 'Define la malla de esta jornada antes de registrar la especialidad del docente.',
            ]);

        expect($docente->especialidades)->toHaveCount(0);
    });

    it('rejects an area that is not in the session malla', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        [$oferta, $asignatura] = especialidadEnJornada($establecimiento, 'Matemática');
        $docente = docenteDe($establecimiento, 'Mora', 'Luis');
        $docente->especialidades()->attach($asignatura->id);
        $ajena = ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
        $otra = Sys_Asignatura::factory()->create(['nombre' => 'Ciencias Naturales']);
        EstablecimientoAsignatura::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $ajena->id,
            'asignatura_id' => $otra->id,
        ]);

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->from(route('Admin.docentes.areas', $docente))
            ->patch(route('Admin.docentes.update', $docente), [
                'areas' => [$otra->area_id],
            ])
            ->assertRedirect(route('Admin.docentes.areas', $docente))
            ->assertSessionHasErrors([
                'areas' => 'Marca las áreas de especialización del docente.',
            ]);

        expect($docente->especialidades()->pluck('sys_asignaturas.id')->all())->toBe([$asignatura->id]);
    });

    it('returns 404 when saving a docente from another establishment', function () {
        $admin = adminOf(Establecimiento::factory()->create());
        $docente = docenteDe(Establecimiento::factory()->create(), 'Colegio', 'Otro');

        $this->actingAs($admin)
            ->patch(route('Admin.docentes.update', $docente), [
                'areas' => [1],
            ])
            ->assertNotFound();
    });

    it('returns 404 when the empleado is not a docente', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        [$oferta, $asignatura] = especialidadEnJornada($establecimiento, 'Matemática');
        $empleado = empleadoDe($establecimiento, Role::Secretaria, 'Perez', 'Ana');

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->patch(route('Admin.docentes.update', $empleado), [
                'areas' => [$asignatura->area_id],
            ])
            ->assertNotFound();

        expect($empleado->especialidades)->toHaveCount(0);
    });

    it('returns 404 when opening areas of a docente from another establishment', function () {
        $admin = adminOf(Establecimiento::factory()->create());
        $docente = docenteDe(Establecimiento::factory()->create(), 'Colegio', 'Otro');

        $this->actingAs($admin)
            ->get(route('Admin.docentes.areas', $docente))
            ->assertNotFound();
    });

    it('forbids systems users from opening areas', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $docente = docenteDe(Establecimiento::factory()->create(), 'Mora', 'Luis');

        $this->actingAs($user)
            ->get(route('Admin.docentes.areas', $docente))
            ->assertForbidden();
    });

    it('forbids systems users from saving a specialty', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $docente = docenteDe(Establecimiento::factory()->create(), 'Mora', 'Luis');

        $this->actingAs($user)
            ->patch(route('Admin.docentes.update', $docente), [])
            ->assertForbidden();
    });
});

function docenteDe(Establecimiento $establecimiento, string $apellidos, string $nombres): Empleado
{
    return empleadoDe($establecimiento, Role::Docente, $apellidos, $nombres);
}

function empleadoDe(Establecimiento $establecimiento, Role $role, string $apellidos, string $nombres): Empleado
{
    $user = assignRole(User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
    ]), $role);
    $persona = Persona::factory()->create([
        'user_id' => $user->id,
        'establecimiento_id' => $establecimiento->id,
        'apellidos' => $apellidos,
        'nombres' => $nombres,
    ]);

    return Empleado::factory()->for($persona)->create();
}
