<?php

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoGrado;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

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
        $aula = EstablecimientoAula::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_grado_id' => $grado->id,
            'paralelo' => 'A',
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
            ->assertSee('A')
            ->assertSeeInOrder(['Id', (string) $aula->id, '8vo Básica'])
            ->assertSee('Crear aula')
            ->assertSee("dispatch('open-modal', 'aula-create')", false)
            ->assertSee('Nueva aula');
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
                'paralelo' => 'b',
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
                'paralelo' => 'Indica el paralelo del aula.',
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
                'paralelo' => 'A-1',
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHasErrorsIn('aula-create', [
                'paralelo' => 'El paralelo solo puede tener letras y números.',
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
                'paralelo' => 'A',
            ])
            ->assertRedirect(route('Admin.aulas'))
            ->assertSessionHasErrorsIn('aula-create', [
                'paralelo' => 'Ya existe un aula con ese paralelo en este grado y periodo.',
            ]);

        $this->assertDatabaseCount('establecimiento_aulas', 1);
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
                'paralelo' => 'A',
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
                'paralelo' => 'A',
            ])
            ->assertForbidden();
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
