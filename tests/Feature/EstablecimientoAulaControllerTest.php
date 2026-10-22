<?php

use App\Enums\Role;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoGrado;
use App\Models\Persona;
use App\Models\Sys_Asignatura;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

/**
 * @return array{0: EstablecimientoAsignatura, 1: Empleado}
 */
function mallaYDocente(Establecimiento $establecimiento, EstablecimientoGrado $grado, string $asignatura, string $apellidos, string $nombres, bool $conEspecialidad = true, int $activo = 1): array
{
    $catalogo = Sys_Asignatura::factory()->create(['nombre' => $asignatura]);
    $malla = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $grado->establecimiento_modalidad_jornada_id,
        'grado_id' => $grado->grado_id,
        'asignatura_id' => $catalogo->id,
    ]);
    $user = assignRole(User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'name' => trim($nombres.' '.$apellidos),
    ]), Role::Docente);
    $persona = Persona::factory()->create([
        'user_id' => $user->id,
        'establecimiento_id' => $establecimiento->id,
        'nombres' => $nombres,
        'apellidos' => $apellidos,
    ]);
    $empleado = Empleado::factory()->for($persona)->create([
        'activo' => $activo,
    ]);

    if ($conEspecialidad) {
        $empleado->especialidades()->attach($catalogo->id);
    }

    return [$malla, $empleado];
}

describe('index', function () {
    it('allows administrators to open aulas of the active oferta and period', function () {
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'Unidad Educativa Andina']);
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento, ['modalidad' => 'Presencial', 'jornada' => 'Matutina']);
        $periodo = periodoActivoDe($oferta, ['nombre' => '2026-2027']);
        ['grado' => $catalogo] = catalogoEstructura(['grado' => '8vo Básica']);
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);
        ['grado' => $otroCatalogo] = catalogoEstructura([
            'nivel' => 'Educación General Básica',
            'subnivel' => 'Básica Media',
            'grado' => '9no Básica',
        ]);
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $otroCatalogo->subnivel_id,
            'grado_id' => $otroCatalogo->id,
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->get(route('Admin.aulas'))
            ->assertOk()
            ->assertSee('Aulas')
            ->assertSee('Unidad Educativa Andina')
            ->assertSee('Presencial · Matutina')
            ->assertSee('2026-2027')
            ->assertSee('8vo Básica')
            ->assertSee('9no Básica')
            ->assertSee('Sin paralelos')
            ->assertSee('A')
            ->assertSee('Crear aula')
            ->assertSee('Configuración')
            ->assertSee('Asignar docentes')
            ->assertSee('Docentes')
            ->assertSee("dispatch('aula-create'", false)
            ->assertSee("dispatch('aula-edit'", false)
            ->assertSee('Nueva aula')
            ->assertSee('Editar paralelo');
    });

    it('does not list aulas of another oferta or establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura(['grado' => 'Octavo propio']);
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);

        $otraOferta = ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
        $otroPeriodo = periodoActivoDe($otraOferta);
        ['grado' => $otroCatalogo] = catalogoEstructura([
            'nivel' => 'Bachillerato vespertino',
            'subnivel' => 'BGU vespertino',
            'grado' => 'Noveno ajeno',
        ]);
        $otroGrado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $otraOferta->id,
            'subnivel_id' => $otroCatalogo->subnivel_id,
            'grado_id' => $otroCatalogo->id,
        ]);
        EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $otraOferta->id,
            'establecimiento_periodo_id' => $otroPeriodo->id,
            'establecimiento_grado_id' => $otroGrado->id,
            'paralelo' => 'Z',
        ]);

        $ajeno = Establecimiento::factory()->create();
        $ofertaAjena = ofertaDe($ajeno);
        $periodoAjeno = periodoActivoDe($ofertaAjena);
        ['grado' => $catalogoAjeno] = catalogoEstructura([
            'nivel' => 'Bachillerato ajeno',
            'subnivel' => 'BGU ajeno',
            'grado' => 'Décimo extraño',
        ]);
        $gradoAjeno = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $ajeno->id,
            'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
            'subnivel_id' => $catalogoAjeno->subnivel_id,
            'grado_id' => $catalogoAjeno->id,
        ]);
        EstablecimientoAula::factory()->create([
            'establecimiento_id' => $ajeno->id,
            'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
            'establecimiento_periodo_id' => $periodoAjeno->id,
            'establecimiento_grado_id' => $gradoAjeno->id,
            'paralelo' => 'X',
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->get(route('Admin.aulas'))
            ->assertOk()
            ->assertSee('A')
            ->assertDontSee('Noveno ajeno')
            ->assertDontSee('Décimo extraño');
    });

    it('explains when the active oferta has no grades', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->get(route('Admin.aulas'))
            ->assertOk()
            ->assertSee('Define los grados de esta modalidad y jornada en')
            ->assertSee(route('Admin.estructura.edit', $oferta), false)
            ->assertDontSee('Crear aula');
    });

    it('explains when there is no active period', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->get(route('Admin.aulas'))
            ->assertOk()
            ->assertSee('No existe ningún periodo activo.')
            ->assertDontSee('Crear aula');
    });

    it('explains when the establishment has no ofertas', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->get(route('Admin.aulas'))
            ->assertOk()
            ->assertSee('Sistemas aún no ha asignado modalidades y jornadas a este establecimiento.')
            ->assertDontSee('Crear aula');
    });

    it('redirects administrators to choose an oferta when several exist and none is selected', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        ofertaDe($establecimiento, ['jornada' => 'Matutina']);
        ofertaDe($establecimiento, ['jornada' => 'Vespertina']);

        $this->actingAs($admin)
            ->get(route('Admin.aulas'))
            ->assertRedirect(route('oferta.select'));
    });

    it('escapes grade names and paralelos on the aulas screen', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura(['grado' => "<script>alert('grado')</script>"]);
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->get(route('Admin.aulas'))
            ->assertSee("<script>alert('grado')</script>")
            ->assertDontSee("<script>alert('grado')</script>", false);
    });

    it('forbids systems users from opening aulas', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get(route('Admin.aulas'))
            ->assertForbidden();
    });

    it('redirects guests from aulas to login', function () {
        $this->get(route('Admin.aulas'))
            ->assertRedirect(route('login'));
    });

    it('forbids administrators without an establishment', function () {
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.aulas'))
            ->assertForbidden();
    });

    it('shows the aulas option to administrators', function () {
        $this->seed(NavigationSeeder::class);
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertSee('Aulas')
            ->assertSee(route('Admin.aulas'), false);
    });
});

describe('store', function () {
    it('creates an aula for the grade and parallel in the active session', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura(['grado' => '8vo Básica']);
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->post(route('Admin.aulas.store'), [
                'establecimiento_grado_id' => $grado->id,
                'paralelos' => 'b',
                'establecimiento_id' => Establecimiento::factory()->create()->id,
                'establecimiento_periodo_id' => 999,
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHas('status', 'aula-created');

        $this->assertDatabaseHas('establecimiento_aulas', [
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'B',
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->get(route('Admin.aulas'))
            ->assertOk()
            ->assertSee('8vo Básica')
            ->assertSee('B');
    });

    it('rejects an empty aula payload', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura();
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.aulas'))
            ->post(route('Admin.aulas.store'), [])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHasErrorsIn('aula-create', [
                'establecimiento_grado_id' => 'Selecciona el grado del aula.',
                'paralelos' => 'Indica al menos un paralelo.',
            ]);

        $this->assertDatabaseCount('establecimiento_aulas', 0);
    });

    it('rejects a parallel that is not letters or numbers', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura();
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.aulas'))
            ->post(route('Admin.aulas.store'), [
                'establecimiento_grado_id' => $grado->id,
                'paralelos' => 'A-1',
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHasErrorsIn('aula-create', [
                'paralelos.0' => 'El paralelo solo puede tener letras y números.',
            ]);

        $this->assertDatabaseCount('establecimiento_aulas', 0);
    });

    it('rejects a duplicate parallel in the same grade and period', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura();
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.aulas'))
            ->post(route('Admin.aulas.store'), [
                'establecimiento_grado_id' => $grado->id,
                'paralelos' => 'A, B',
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHasErrorsIn('aula-create', [
                'paralelos' => 'Ya existe un aula con el paralelo A en este grado y periodo.',
            ]);

        $this->assertDatabaseCount('establecimiento_aulas', 1);
        $this->assertDatabaseMissing('establecimiento_aulas', [
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'B',
        ]);
    });

    it('creates one aula for each parallel of the selected grade', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura(['grado' => 'Segundo de EGB']);
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->post(route('Admin.aulas.store'), [
                'establecimiento_grado_id' => $grado->id,
                'paralelos' => 'a, b, c',
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHas('status', 'aula-created')
            ->assertSessionHas('aulas_creadas', 3);

        expect(EstablecimientoAula::query()->orderBy('paralelo')->pluck('paralelo')->all())->toBe(['A', 'B', 'C'])
            ->and(EstablecimientoAula::query()->where('establecimiento_grado_id', '!=', $grado->id)->count())->toBe(0);
    });

    it('rejects a parallel repeated in the same request', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura();
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.aulas'))
            ->post(route('Admin.aulas.store'), [
                'establecimiento_grado_id' => $grado->id,
                'paralelos' => 'A, a',
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHasErrorsIn('aula-create', [
                'paralelos.1' => 'Cada paralelo debe escribirse una sola vez.',
            ]);

        $this->assertDatabaseCount('establecimiento_aulas', 0);
    });

    it('rejects a grade that does not belong to the active oferta', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura();
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);

        $ajeno = Establecimiento::factory()->create();
        $ofertaAjena = ofertaDe($ajeno);
        ['grado' => $catalogoAjeno] = catalogoEstructura([
            'nivel' => 'Nivel ajeno',
            'subnivel' => 'Subnivel ajeno',
            'grado' => 'Grado ajeno',
        ]);
        $gradoAjeno = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $ajeno->id,
            'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
            'subnivel_id' => $catalogoAjeno->subnivel_id,
            'grado_id' => $catalogoAjeno->id,
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.aulas'))
            ->post(route('Admin.aulas.store'), [
                'establecimiento_grado_id' => $gradoAjeno->id,
                'paralelos' => 'A',
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHasErrorsIn('aula-create', [
                'establecimiento_grado_id' => 'El grado no pertenece a la modalidad y jornada activas.',
            ]);

        $this->assertDatabaseCount('establecimiento_aulas', 0);
    });

    it('forbids systems users from creating an aula', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->post(route('Admin.aulas.store'), [
                'paralelos' => 'A',
            ])
            ->assertForbidden();
    });
});

describe('update', function () {
    it('renames the parallel of an aula in the active session', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura(['grado' => 'Segundo de EGB']);
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        $aula = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.aulas'))
            ->patch(route('Admin.aulas.update', $aula), [
                'paralelo' => 'c',
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHas('status', 'aula-updated');

        $this->assertDatabaseHas('establecimiento_aulas', [
            'id' => $aula->id,
            'paralelo' => 'C',
        ]);
    });

    it('rejects a parallel that already exists in the same grade', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura();
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);
        $aula = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'B',
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.aulas'))
            ->patch(route('Admin.aulas.update', $aula), [
                'paralelo' => 'A',
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHasErrorsIn('aula-edit', [
                'paralelo' => 'Ya existe un aula con ese paralelo en este grado y periodo.',
            ]);

        $this->assertDatabaseHas('establecimiento_aulas', [
            'id' => $aula->id,
            'paralelo' => 'B',
        ]);
    });

    it('returns 404 when editing an aula of another establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);

        $ajeno = Establecimiento::factory()->create();
        $ofertaAjena = ofertaDe($ajeno);
        $periodoAjeno = periodoActivoDe($ofertaAjena);
        ['grado' => $catalogo] = catalogoEstructura();
        $gradoAjeno = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $ajeno->id,
            'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        $aulaAjena = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $ajeno->id,
            'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
            'establecimiento_periodo_id' => $periodoAjeno->id,
            'establecimiento_grado_id' => $gradoAjeno->id,
            'paralelo' => 'A',
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->patch(route('Admin.aulas.update', $aulaAjena), [
                'paralelo' => 'C',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('establecimiento_aulas', [
            'id' => $aulaAjena->id,
            'paralelo' => 'A',
        ]);
    });
});

describe('destroy', function () {
    it('deletes an aula of the active session', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura();
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        $aula = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->delete(route('Admin.aulas.destroy', $aula))
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHas('status', 'aula-deleted');

        $this->assertDatabaseMissing('establecimiento_aulas', [
            'id' => $aula->id,
        ]);
    });

    it('returns 404 when deleting an aula of another establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);

        $ajeno = Establecimiento::factory()->create();
        $ofertaAjena = ofertaDe($ajeno);
        $periodoAjeno = periodoActivoDe($ofertaAjena);
        ['grado' => $catalogo] = catalogoEstructura();
        $gradoAjeno = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $ajeno->id,
            'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        $aulaAjena = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $ajeno->id,
            'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
            'establecimiento_periodo_id' => $periodoAjeno->id,
            'establecimiento_grado_id' => $gradoAjeno->id,
            'paralelo' => 'A',
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->delete(route('Admin.aulas.destroy', $aulaAjena))
            ->assertNotFound();

        $this->assertModelExists($aulaAjena);
    });
});

describe('docentes', function () {
    it('offers the specialists of a subject and every active docente when nobody has that specialty', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura(['grado' => '8vo Básica']);
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);
        [$matematica, $ana] = mallaYDocente($establecimiento, $grado, 'Matemática', 'Perez', 'Ana');

        $sinEspecialidad = assignRole(User::factory()->create([
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Docente);
        $personaSinEspecialidad = Persona::factory()->create([
            'user_id' => $sinEspecialidad->id,
            'establecimiento_id' => $establecimiento->id,
            'nombres' => 'Luis',
            'apellidos' => 'Mora',
        ]);
        $luis = Empleado::factory()->for($personaSinEspecialidad)->create();

        [$ciencias, $inactivo] = mallaYDocente($establecimiento, $grado, 'Ciencias Naturales', 'Diaz', 'Inactivo', true, 0);

        $ajeno = Establecimiento::factory()->create();
        $docenteAjeno = assignRole(User::factory()->create([
            'establecimiento_id' => $ajeno->id,
        ]), Role::Docente);
        $personaAjena = Persona::factory()->create([
            'user_id' => $docenteAjeno->id,
            'establecimiento_id' => $ajeno->id,
            'nombres' => 'Soto',
            'apellidos' => 'Ajeno',
        ]);
        $empleadoAjeno = Empleado::factory()->for($personaAjena)->create();
        $empleadoAjeno->especialidades()->attach($matematica->asignatura_id);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->get(route('Admin.aulas'))
            ->assertOk()
            ->assertSee('Asignar docentes')
            ->assertSee('8vo Básica A')
            ->assertSee('Grado')
            ->assertSee('Paralelos')
            ->assertSee('Matemática')
            ->assertSee('Perez Ana')
            ->assertSee('Mora Luis')
            ->assertDontSee('Diaz Inactivo')
            ->assertDontSee('Ajeno Soto')
            ->assertViewHas('docentesPorAsignatura', function (array $docentes) use ($matematica, $ciencias, $ana, $luis, $inactivo): bool {
                $deMatematica = array_column($docentes[$matematica->asignatura_id], 'id');
                $deCiencias = array_column($docentes[$ciencias->asignatura_id], 'id');

                expect($deMatematica)->toBe([$ana->id])
                    ->and($deCiencias)->toBe([$luis->id, $ana->id])
                    ->and($deCiencias)->not->toContain($inactivo->id);

                return true;
            });
    });

    it('assigns one docente to each subject of the aula', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura();
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        $aula = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);
        [$matematica, $ana] = mallaYDocente($establecimiento, $grado, 'Matemática', 'Perez', 'Ana');
        [$ingles, $bruno] = mallaYDocente($establecimiento, $grado, 'Inglés', 'Vega', 'Bruno');

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->patch(route('Admin.aulas.docentes.update', $aula), [
                'docentes' => [
                    $matematica->id => $ana->id,
                    $ingles->id => $bruno->id,
                ],
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHas('status', 'aula-docentes-updated')
            ->assertSessionHas('pestana', 'docentes');

        $this->assertDatabaseHas('establecimiento_aula_docentes', [
            'establecimiento_aula_id' => $aula->id,
            'establecimiento_asignatura_id' => $matematica->id,
            'empleado_id' => $ana->id,
        ]);
        $this->assertDatabaseHas('establecimiento_aula_docentes', [
            'establecimiento_aula_id' => $aula->id,
            'establecimiento_asignatura_id' => $ingles->id,
            'empleado_id' => $bruno->id,
        ]);
    });

    it('replaces the docente already assigned to a subject', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura();
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        $aula = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);
        [$matematica, $ana] = mallaYDocente($establecimiento, $grado, 'Matemática', 'Perez', 'Ana');
        $brunoUser = assignRole(User::factory()->create([
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Docente);
        $brunoPersona = Persona::factory()->create([
            'user_id' => $brunoUser->id,
            'establecimiento_id' => $establecimiento->id,
            'nombres' => 'Bruno',
            'apellidos' => 'Vega',
        ]);
        $bruno = Empleado::factory()->for($brunoPersona)->create();
        $bruno->especialidades()->attach($matematica->asignatura_id);
        EstablecimientoAulaDocente::factory()->create([
            'establecimiento_aula_id' => $aula->id,
            'establecimiento_asignatura_id' => $matematica->id,
            'empleado_id' => $ana->id,
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->patch(route('Admin.aulas.docentes.update', $aula), [
                'docentes' => [
                    $matematica->id => $bruno->id,
                ],
            ])
            ->assertRedirect(route('Admin.aulas'));

        $this->assertDatabaseHas('establecimiento_aula_docentes', [
            'establecimiento_aula_id' => $aula->id,
            'establecimiento_asignatura_id' => $matematica->id,
            'empleado_id' => $bruno->id,
        ]);
        $this->assertDatabaseMissing('establecimiento_aula_docentes', [
            'establecimiento_aula_id' => $aula->id,
            'empleado_id' => $ana->id,
        ]);
    });

    it('removes the docente when the subject is left empty', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura();
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        $aula = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);
        [$matematica, $ana] = mallaYDocente($establecimiento, $grado, 'Matemática', 'Perez', 'Ana');
        EstablecimientoAulaDocente::factory()->create([
            'establecimiento_aula_id' => $aula->id,
            'establecimiento_asignatura_id' => $matematica->id,
            'empleado_id' => $ana->id,
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->patch(route('Admin.aulas.docentes.update', $aula), [
                'docentes' => [
                    $matematica->id => '',
                ],
            ])
            ->assertRedirect(route('Admin.aulas'));

        $this->assertDatabaseMissing('establecimiento_aula_docentes', [
            'establecimiento_aula_id' => $aula->id,
        ]);
    });

    it('assigns any active docente when nobody has that subject as a specialty', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura();
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        $aula = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);
        [$matematica] = mallaYDocente($establecimiento, $grado, 'Matemática', 'Perez', 'Ana', false);
        $luisUser = assignRole(User::factory()->create([
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Docente);
        $luisPersona = Persona::factory()->create([
            'user_id' => $luisUser->id,
            'establecimiento_id' => $establecimiento->id,
            'nombres' => 'Luis',
            'apellidos' => 'Mora',
        ]);
        $luis = Empleado::factory()->for($luisPersona)->create();

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->patch(route('Admin.aulas.docentes.update', $aula), [
                'docentes' => [
                    $matematica->id => $luis->id,
                ],
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHas('status', 'aula-docentes-updated');

        $this->assertDatabaseHas('establecimiento_aula_docentes', [
            'establecimiento_aula_id' => $aula->id,
            'establecimiento_asignatura_id' => $matematica->id,
            'empleado_id' => $luis->id,
        ]);
    });

    it('rejects a docente who does not have that subject and keeps the current assignment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura();
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        $aula = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);
        [$matematica, $ana] = mallaYDocente($establecimiento, $grado, 'Matemática', 'Perez', 'Ana');
        $luisUser = assignRole(User::factory()->create([
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Docente);
        $luisPersona = Persona::factory()->create([
            'user_id' => $luisUser->id,
            'establecimiento_id' => $establecimiento->id,
            'nombres' => 'Luis',
            'apellidos' => 'Mora',
        ]);
        $luis = Empleado::factory()->for($luisPersona)->create();
        EstablecimientoAulaDocente::factory()->create([
            'establecimiento_aula_id' => $aula->id,
            'establecimiento_asignatura_id' => $matematica->id,
            'empleado_id' => $ana->id,
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.aulas'))
            ->patch(route('Admin.aulas.docentes.update', $aula), [
                'docentes' => [
                    $matematica->id => $luis->id,
                ],
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHasErrorsIn('aula-docentes', [
                'docentes.'.$matematica->id => 'Selecciona un docente que tenga esta asignatura como especialidad.',
            ]);

        $this->assertDatabaseHas('establecimiento_aula_docentes', [
            'establecimiento_aula_id' => $aula->id,
            'empleado_id' => $ana->id,
        ]);
    });

    it('rejects a subject that does not belong to the aula grade', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura(['grado' => '8vo Básica']);
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        ['grado' => $otroCatalogo] = catalogoEstructura([
            'nivel' => 'Educación General Básica',
            'subnivel' => 'Básica Media',
            'grado' => '9no Básica',
        ]);
        $otroGrado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $otroCatalogo->subnivel_id,
            'grado_id' => $otroCatalogo->id,
        ]);
        $aula = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);
        [$otraMalla, $ana] = mallaYDocente($establecimiento, $otroGrado, 'Inglés', 'Perez', 'Ana');

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.aulas'))
            ->patch(route('Admin.aulas.docentes.update', $aula), [
                'docentes' => [
                    $otraMalla->id => $ana->id,
                ],
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHasErrorsIn('aula-docentes', [
                'docentes' => 'La asignatura no pertenece al grado de esta aula.',
            ]);

        $this->assertDatabaseCount('establecimiento_aula_docentes', 0);
    });

    it('returns 404 when assigning docentes to an aula of another establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);

        $ajeno = Establecimiento::factory()->create();
        $ofertaAjena = ofertaDe($ajeno);
        $periodoAjeno = periodoActivoDe($ofertaAjena);
        ['grado' => $catalogo] = catalogoEstructura();
        $gradoAjeno = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $ajeno->id,
            'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        $aulaAjena = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $ajeno->id,
            'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
            'establecimiento_periodo_id' => $periodoAjeno->id,
            'establecimiento_grado_id' => $gradoAjeno->id,
            'paralelo' => 'A',
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->patch(route('Admin.aulas.docentes.update', $aulaAjena), [
                'docentes' => [],
            ])
            ->assertNotFound();
    });

    it('assigns the same docentes to other selected paralelos of the grade', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura();
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
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
        $aulaC = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'C',
        ]);
        [$matematica, $ana] = mallaYDocente($establecimiento, $grado, 'Matemática', 'Perez', 'Ana');

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->patch(route('Admin.aulas.docentes.update', $aulaA), [
                'aulas' => [$aulaA->id, $aulaB->id],
                'docentes' => [
                    $matematica->id => $ana->id,
                ],
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHas('status', 'aula-docentes-updated');

        $this->assertDatabaseHas('establecimiento_aula_docentes', [
            'establecimiento_aula_id' => $aulaA->id,
            'establecimiento_asignatura_id' => $matematica->id,
            'empleado_id' => $ana->id,
        ]);
        $this->assertDatabaseHas('establecimiento_aula_docentes', [
            'establecimiento_aula_id' => $aulaB->id,
            'establecimiento_asignatura_id' => $matematica->id,
            'empleado_id' => $ana->id,
        ]);
        $this->assertDatabaseMissing('establecimiento_aula_docentes', [
            'establecimiento_aula_id' => $aulaC->id,
        ]);
    });

    it('keeps other paralelos unchanged when they are not selected', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura();
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
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
        [$matematica, $ana] = mallaYDocente($establecimiento, $grado, 'Matemática', 'Perez', 'Ana');
        EstablecimientoAulaDocente::factory()->create([
            'establecimiento_aula_id' => $aulaB->id,
            'establecimiento_asignatura_id' => $matematica->id,
            'empleado_id' => $ana->id,
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->patch(route('Admin.aulas.docentes.update', $aulaA), [
                'docentes' => [
                    $matematica->id => $ana->id,
                ],
            ])
            ->assertRedirect(route('Admin.aulas'));

        $this->assertDatabaseHas('establecimiento_aula_docentes', [
            'establecimiento_aula_id' => $aulaA->id,
            'empleado_id' => $ana->id,
        ]);
        $this->assertDatabaseHas('establecimiento_aula_docentes', [
            'establecimiento_aula_id' => $aulaB->id,
            'empleado_id' => $ana->id,
        ]);
    });

    it('rejects paralelos that belong to another grade', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura(['grado' => '8vo Básica']);
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        ['grado' => $otroCatalogo] = catalogoEstructura([
            'nivel' => 'Educación General Básica',
            'subnivel' => 'Básica Media',
            'grado' => '9no Básica',
        ]);
        $otroGrado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $otroCatalogo->subnivel_id,
            'grado_id' => $otroCatalogo->id,
        ]);
        $aula = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);
        $otraAula = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $otroGrado->id,
            'paralelo' => 'A',
        ]);
        [$matematica, $ana] = mallaYDocente($establecimiento, $grado, 'Matemática', 'Perez', 'Ana');

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.aulas'))
            ->patch(route('Admin.aulas.docentes.update', $aula), [
                'aulas' => [$aula->id, $otraAula->id],
                'docentes' => [
                    $matematica->id => $ana->id,
                ],
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHasErrorsIn('aula-docentes', [
                'aulas' => 'Selecciona paralelos del mismo grado.',
            ]);

        $this->assertDatabaseCount('establecimiento_aula_docentes', 0);
    });

    it('returns 404 when a selected paralelo belongs to another establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        ['grado' => $catalogo] = catalogoEstructura();
        $grado = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        $aula = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
        ]);
        [$matematica, $ana] = mallaYDocente($establecimiento, $grado, 'Matemática', 'Perez', 'Ana');

        $ajeno = Establecimiento::factory()->create();
        $ofertaAjena = ofertaDe($ajeno);
        $periodoAjeno = periodoActivoDe($ofertaAjena);
        $gradoAjeno = EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $ajeno->id,
            'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
            'subnivel_id' => $catalogo->subnivel_id,
            'grado_id' => $catalogo->id,
        ]);
        $aulaAjena = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $ajeno->id,
            'establecimiento_modalidad_jornada_id' => $ofertaAjena->id,
            'establecimiento_periodo_id' => $periodoAjeno->id,
            'establecimiento_grado_id' => $gradoAjeno->id,
            'paralelo' => 'A',
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->patch(route('Admin.aulas.docentes.update', $aula), [
                'aulas' => [$aula->id, $aulaAjena->id],
                'docentes' => [
                    $matematica->id => $ana->id,
                ],
            ])
            ->assertNotFound();
    });
});
