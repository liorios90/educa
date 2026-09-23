<?php

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoNivel;
use App\Models\EstablecimientoSubnivel;
use App\Models\Sys_Grado;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

/**
 * @return array{nivel: Sys_Nivel, subnivel: Sys_Subnivel, grado: Sys_Grado}
 */
function catalogoEstructura(array $nombres = []): array
{
    $nivel = Sys_Nivel::factory()->create(['nombre' => $nombres['nivel'] ?? 'Educación Inicial']);
    $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create(['nombre' => $nombres['subnivel'] ?? 'Inicial 2']);
    $grado = Sys_Grado::factory()->for($subnivel, 'subnivel')->create(['nombre' => $nombres['grado'] ?? 'Segundo de inicial']);

    return compact('nivel', 'subnivel', 'grado');
}

describe('edit', function () {
    it('allows administrators to see the national catalog of their establishment', function () {
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'Unidad Educativa Andina']);
        $admin = adminOf($establecimiento);
        catalogoEstructura();
        catalogoEstructura([
            'nivel' => 'Bachillerato',
            'subnivel' => 'Bachillerato General Unificado',
            'grado' => 'Primero de BGU',
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.estructura'))
            ->assertOk()
            ->assertSee('Estructura educativa')
            ->assertSee('Unidad Educativa Andina')
            ->assertSee('Niveles del catálogo')
            ->assertSee('Nivel: Educación Inicial')
            ->assertSee('Bachillerato')
            ->assertSee('Subnivel: Inicial 2')
            ->assertSee('Grados')
            ->assertSee('Segundo de inicial')
            ->assertSee('name="grados[]"', false)
            ->assertSee('name="nombre_grados[', false)
            ->assertSee('name="nombre_subniveles[', false)
            ->assertSee('aria-label="Nombre en el establecimiento de Inicial 2"', false)
            ->assertSee('aria-label="Nombre en el establecimiento de Segundo de inicial"', false)
            ->assertSee('Nombre en el establecimiento')
            ->assertDontSee('name="niveles[]"', false)
            ->assertDontSee('name="subniveles[]"', false)
            ->assertSee('Guardar estructura');
    });

    it('shows previously selected items for the establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        ['subnivel' => $subnivel, 'grado' => $grado] = catalogoEstructura();
        EstablecimientoSubnivel::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'nivel_id' => $subnivel->nivel_id,
            'subnivel_id' => $subnivel->id,
            'nombre' => 'Prekínder institucional',
        ]);
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'subnivel_id' => $grado->subnivel_id,
            'grado_id' => $grado->id,
            'nombre' => 'Kínder',
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.estructura'))
            ->assertOk()
            ->assertViewHas('selectedGradoIds', [(string) $grado->id])
            ->assertSee('Kínder')
            ->assertSee('Prekínder institucional');
    });

    it('does not mark another establishment selection as checked', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $otro = Establecimiento::factory()->create();
        ['grado' => $grado] = catalogoEstructura();
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $otro->id,
            'subnivel_id' => $grado->subnivel_id,
            'grado_id' => $grado->id,
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.estructura'))
            ->assertOk()
            ->assertViewHas('selectedGradoIds', []);
    });

    it('forbids systems users from opening establishment estructura', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get(route('Admin.estructura'))
            ->assertForbidden();
    });

    it('redirects guests from establishment estructura to login', function () {
        $this->get(route('Admin.estructura'))
            ->assertRedirect(route('login'));
    });

    it('forbids administrators without an establishment', function () {
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.estructura'))
            ->assertForbidden();
    });

    it('escapes catalog names in the estructura form', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        catalogoEstructura([
            'nivel' => "<script>alert('xss')</script>",
            'subnivel' => 'Inicial 2',
            'grado' => 'Segundo de inicial',
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.estructura'))
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });

    it('escapes custom establishment names in the estructura form', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        ['subnivel' => $subnivel, 'grado' => $grado] = catalogoEstructura();
        EstablecimientoSubnivel::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'nivel_id' => $subnivel->nivel_id,
            'subnivel_id' => $subnivel->id,
            'nombre' => "<script>alert('sub')</script>",
        ]);
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'subnivel_id' => $grado->subnivel_id,
            'grado_id' => $grado->id,
            'nombre' => "<script>alert('grado')</script>",
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.estructura'))
            ->assertSee("<script>alert('sub')</script>")
            ->assertSee("<script>alert('grado')</script>")
            ->assertDontSee("<script>alert('sub')</script>", false)
            ->assertDontSee("<script>alert('grado')</script>", false);
    });

    it('shows the estructura option to administrators', function () {
        $this->seed(NavigationSeeder::class);
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertSee('Estructura')
            ->assertSee(route('Admin.estructura'), false);
    });
});

describe('update', function () {
    it('saves the nivel and subnivel that belong to each selected grado', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        ['nivel' => $nivel, 'subnivel' => $subnivel, 'grado' => $grado] = catalogoEstructura();
        $otroNivel = Sys_Nivel::factory()->create(['nombre' => 'Bachillerato']);

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update'), [
                'grados' => [$grado->id],
            ])
            ->assertRedirect(route('Admin.estructura'))
            ->assertSessionHas('status', 'estructura-updated');

        $this->assertDatabaseHas('establecimiento_niveles', [
            'establecimiento_id' => $establecimiento->id,
            'nivel_id' => $nivel->id,
        ]);
        $this->assertDatabaseHas('establecimiento_subniveles', [
            'establecimiento_id' => $establecimiento->id,
            'nivel_id' => $nivel->id,
            'subnivel_id' => $subnivel->id,
        ]);
        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_id' => $establecimiento->id,
            'subnivel_id' => $subnivel->id,
            'grado_id' => $grado->id,
            'nombre' => null,
        ]);
        $this->assertDatabaseMissing('establecimiento_niveles', [
            'establecimiento_id' => $establecimiento->id,
            'nivel_id' => $otroNivel->id,
        ]);
    });

    it('saves custom names for the selected grado and its subnivel', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        ['subnivel' => $subnivel, 'grado' => $grado] = catalogoEstructura();

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update'), [
                'grados' => [$grado->id],
                'nombre_grados' => [$grado->id => '  Kínder  '],
                'nombre_subniveles' => [$subnivel->id => 'Prekínder institucional'],
            ])
            ->assertRedirect(route('Admin.estructura'))
            ->assertSessionHas('status', 'estructura-updated');

        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_id' => $establecimiento->id,
            'grado_id' => $grado->id,
            'nombre' => 'Kínder',
        ]);
        $this->assertDatabaseHas('establecimiento_subniveles', [
            'establecimiento_id' => $establecimiento->id,
            'subnivel_id' => $subnivel->id,
            'nombre' => 'Prekínder institucional',
        ]);
    });

    it('ignores custom names of grados that were not selected', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $inicial = catalogoEstructura(['grado' => 'Inicial 2']);
        $egb = catalogoEstructura([
            'nivel' => 'Educación General Básica',
            'subnivel' => 'Preparatoria',
            'grado' => 'Primero de EGB',
        ]);

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update'), [
                'grados' => [$inicial['grado']->id],
                'nombre_grados' => [
                    $inicial['grado']->id => 'Kínder',
                    $egb['grado']->id => 'Primero institucional',
                ],
                'nombre_subniveles' => [
                    $inicial['subnivel']->id => 'Inicial propio',
                    $egb['subnivel']->id => 'Preparatoria propia',
                ],
            ])
            ->assertRedirect(route('Admin.estructura'));

        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_id' => $establecimiento->id,
            'grado_id' => $inicial['grado']->id,
            'nombre' => 'Kínder',
        ]);
        $this->assertDatabaseMissing('establecimiento_grados', [
            'establecimiento_id' => $establecimiento->id,
            'grado_id' => $egb['grado']->id,
        ]);
        $this->assertDatabaseMissing('establecimiento_subniveles', [
            'establecimiento_id' => $establecimiento->id,
            'subnivel_id' => $egb['subnivel']->id,
        ]);
    });

    it('stores a blank custom name as null', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        ['subnivel' => $subnivel, 'grado' => $grado] = catalogoEstructura();

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update'), [
                'grados' => [$grado->id],
                'nombre_grados' => [$grado->id => '   '],
                'nombre_subniveles' => [$subnivel->id => ''],
            ])
            ->assertRedirect(route('Admin.estructura'));

        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_id' => $establecimiento->id,
            'grado_id' => $grado->id,
            'nombre' => null,
        ]);
        $this->assertDatabaseHas('establecimiento_subniveles', [
            'establecimiento_id' => $establecimiento->id,
            'subnivel_id' => $subnivel->id,
            'nombre' => null,
        ]);
    });

    it('rejects a custom name that is longer than 150 characters', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        ['grado' => $grado] = catalogoEstructura();

        $this->actingAs($admin)
            ->from(route('Admin.estructura'))
            ->put(route('Admin.estructura.update'), [
                'grados' => [$grado->id],
                'nombre_grados' => [$grado->id => str_repeat('a', 151)],
            ])
            ->assertRedirect(route('Admin.estructura'))
            ->assertSessionHasErrors(['nombre_grados.'.$grado->id => 'El nombre del grado en el establecimiento no puede tener más de 150 caracteres.']);

        $this->assertDatabaseCount('establecimiento_grados', 0);
    });

    it('ignores submitted niveles that do not belong to a selected grado', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        ['nivel' => $nivel, 'grado' => $grado] = catalogoEstructura();
        $otroNivel = Sys_Nivel::factory()->create(['nombre' => 'Bachillerato']);

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update'), [
                'niveles' => [$otroNivel->id],
                'subniveles' => [999],
                'grados' => [$grado->id],
            ])
            ->assertRedirect(route('Admin.estructura'))
            ->assertSessionHas('status', 'estructura-updated');

        $this->assertDatabaseHas('establecimiento_niveles', [
            'establecimiento_id' => $establecimiento->id,
            'nivel_id' => $nivel->id,
        ]);
        $this->assertDatabaseMissing('establecimiento_niveles', [
            'establecimiento_id' => $establecimiento->id,
            'nivel_id' => $otroNivel->id,
        ]);
    });

    it('clears the establishment structure when nothing is selected', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        ['nivel' => $nivel, 'subnivel' => $subnivel, 'grado' => $grado] = catalogoEstructura();
        EstablecimientoNivel::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'nivel_id' => $nivel->id,
        ]);
        EstablecimientoSubnivel::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'nivel_id' => $nivel->id,
            'subnivel_id' => $subnivel->id,
        ]);
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'subnivel_id' => $subnivel->id,
            'grado_id' => $grado->id,
        ]);

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update'), [])
            ->assertRedirect(route('Admin.estructura'))
            ->assertSessionHas('status', 'estructura-updated');

        $this->assertDatabaseMissing('establecimiento_niveles', [
            'establecimiento_id' => $establecimiento->id,
        ]);
        $this->assertDatabaseMissing('establecimiento_subniveles', [
            'establecimiento_id' => $establecimiento->id,
        ]);
        $this->assertDatabaseMissing('establecimiento_grados', [
            'establecimiento_id' => $establecimiento->id,
        ]);
    });

    it('replaces a previous selection for the same establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $inicial = catalogoEstructura();
        $bachillerato = catalogoEstructura([
            'nivel' => 'Bachillerato',
            'subnivel' => 'Bachillerato General Unificado',
            'grado' => 'Primero de BGU',
        ]);
        EstablecimientoNivel::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'nivel_id' => $inicial['nivel']->id,
        ]);

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update'), [
                'grados' => [$bachillerato['grado']->id],
            ])
            ->assertRedirect(route('Admin.estructura'));

        $this->assertDatabaseMissing('establecimiento_niveles', [
            'establecimiento_id' => $establecimiento->id,
            'nivel_id' => $inicial['nivel']->id,
        ]);
        $this->assertDatabaseHas('establecimiento_niveles', [
            'establecimiento_id' => $establecimiento->id,
            'nivel_id' => $bachillerato['nivel']->id,
        ]);
        $this->assertDatabaseHas('establecimiento_subniveles', [
            'establecimiento_id' => $establecimiento->id,
            'subnivel_id' => $bachillerato['subnivel']->id,
        ]);
        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_id' => $establecimiento->id,
            'grado_id' => $bachillerato['grado']->id,
        ]);
    });

    it('does not change the structure of another establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $otro = Establecimiento::factory()->create();
        $propia = catalogoEstructura(['nivel' => 'Educación Inicial']);
        $ajena = catalogoEstructura([
            'nivel' => 'Educación General Básica',
            'subnivel' => 'Elemental',
            'grado' => 'Segundo de EGB',
        ]);
        EstablecimientoNivel::factory()->create([
            'establecimiento_id' => $otro->id,
            'nivel_id' => $ajena['nivel']->id,
        ]);
        EstablecimientoSubnivel::factory()->create([
            'establecimiento_id' => $otro->id,
            'nivel_id' => $ajena['nivel']->id,
            'subnivel_id' => $ajena['subnivel']->id,
        ]);
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $otro->id,
            'subnivel_id' => $ajena['subnivel']->id,
            'grado_id' => $ajena['grado']->id,
        ]);

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update'), [
                'grados' => [$propia['grado']->id],
            ])
            ->assertRedirect(route('Admin.estructura'));

        $this->assertDatabaseHas('establecimiento_niveles', [
            'establecimiento_id' => $otro->id,
            'nivel_id' => $ajena['nivel']->id,
        ]);
        $this->assertDatabaseHas('establecimiento_subniveles', [
            'establecimiento_id' => $otro->id,
            'subnivel_id' => $ajena['subnivel']->id,
        ]);
        $this->assertDatabaseHas('establecimiento_grados', [
            'establecimiento_id' => $otro->id,
            'grado_id' => $ajena['grado']->id,
        ]);
        $this->assertDatabaseMissing('establecimiento_niveles', [
            'establecimiento_id' => $otro->id,
            'nivel_id' => $propia['nivel']->id,
        ]);
    });

    it('rejects a grado that has no subnivel in the catalog', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $grado = Sys_Grado::factory()->create([
            'nombre' => 'Sin subnivel',
            'subnivel_id' => null,
        ]);

        $this->actingAs($admin)
            ->from(route('Admin.estructura'))
            ->put(route('Admin.estructura.update'), [
                'grados' => [$grado->id],
            ])
            ->assertRedirect(route('Admin.estructura'))
            ->assertSessionHasErrors(['grados' => 'Cada grado debe pertenecer a un subnivel y un nivel del catálogo.']);

        $this->assertDatabaseCount('establecimiento_grados', 0);
        $this->assertDatabaseCount('establecimiento_niveles', 0);
    });

    it('rejects catalog ids that do not exist', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->from(route('Admin.estructura'))
            ->put(route('Admin.estructura.update'), [
                'grados' => [999],
            ])
            ->assertRedirect(route('Admin.estructura'))
            ->assertSessionHasErrors(['grados.0' => 'El grado seleccionado no existe en el catálogo de sistemas.']);

        $this->assertDatabaseCount('establecimiento_grados', 0);
    });

    it('forbids systems users from saving establishment estructura', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->put(route('Admin.estructura.update'), [])
            ->assertForbidden();
    });

    it('forbids administrators without an establishment from saving estructura', function () {
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->put(route('Admin.estructura.update'), [])
            ->assertForbidden();
    });
});
