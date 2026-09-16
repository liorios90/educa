<?php

use App\Enums\Role;
use App\Models\NavigationItem;
use App\Models\Sys_Circuito;
use App\Models\Sys_Distrito;
use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
use App\Models\Sys_Zona;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

describe('index', function () {
    it('allows systems users to open the jornadas catalog', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        Sys_Jornada::factory()->create(['nombre' => 'Matutina']);

        $this->actingAs($user)
            ->get(route('sistemas.crud.jornadas.index'))
            ->assertOk()
            ->assertSee('Jornadas')
            ->assertSee('Nueva jornada')
            ->assertSee('Matutina');
    });

    it('allows systems users to open the modalidades catalog', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        Sys_Modalidad::factory()->create(['nombre' => 'Presencial']);

        $this->actingAs($user)
            ->get(route('sistemas.crud.modalidades.index'))
            ->assertOk()
            ->assertSee('Modalidades')
            ->assertSee('Presencial');
    });

    it('allows systems users to open the zonas catalog', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        Sys_Zona::factory()->create(['nombre' => 'Zona 1']);

        $this->actingAs($user)
            ->get(route('sistemas.crud.zonas.index'))
            ->assertOk()
            ->assertSee('Zonas')
            ->assertSee('Zona 1');
    });

    it('allows systems users to open the distritos catalog', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        Sys_Distrito::factory()->create(['nombre' => 'Distrito 09D01']);

        $this->actingAs($user)
            ->get(route('sistemas.crud.distritos.index'))
            ->assertOk()
            ->assertSee('Distritos')
            ->assertSee('Distrito 09D01');
    });

    it('allows systems users to open the circuitos catalog', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        Sys_Circuito::factory()->create(['nombre' => 'Circuito 09D01C01']);

        $this->actingAs($user)
            ->get(route('sistemas.crud.circuitos.index'))
            ->assertOk()
            ->assertSee('Circuitos')
            ->assertSee('Circuito 09D01C01');
    });

    it('does not expose establecimientos as a catalog', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get('/sistemas/catalogos/establecimientos')
            ->assertNotFound();
    });

    it('forbids administrators from opening the catalog', function () {
        $user = assignRole(User::factory()->create(), Role::Admin);

        $this->actingAs($user)
            ->get(route('sistemas.crud.jornadas.index'))
            ->assertForbidden();
    });

    it('redirects guests from the catalog to login', function () {
        $this->get(route('sistemas.crud.jornadas.index'))
            ->assertRedirect(route('login'));
    });

    it('escapes field values in the catalog list', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        Sys_Jornada::factory()->create([
            'nombre' => "<script>alert('xss')</script>",
        ]);

        $this->actingAs($user)
            ->get(route('sistemas.crud.jornadas.index'))
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });
});

describe('store', function () {
    it('creates a jornada', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->post(route('sistemas.crud.jornadas.store'), [
                'nombre' => 'Vespertina',
                'descripcion' => 'Turno de la tarde',
            ])
            ->assertRedirect(route('sistemas.crud.jornadas.index'))
            ->assertSessionHas('status', 'crud-created');

        $this->assertDatabaseHas('sys_jornadas', [
            'nombre' => 'Vespertina',
            'descripcion' => 'Turno de la tarde',
        ]);
    });

    it('rejects an empty payload', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->from(route('sistemas.crud.jornadas.create'))
            ->post(route('sistemas.crud.jornadas.store'), [])
            ->assertRedirect(route('sistemas.crud.jornadas.create'))
            ->assertSessionHasErrors(['nombre']);
    });

    it('rejects a duplicate nombre', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        Sys_Jornada::factory()->create(['nombre' => 'Nocturna']);

        $this->actingAs($actor)
            ->from(route('sistemas.crud.jornadas.create'))
            ->post(route('sistemas.crud.jornadas.store'), [
                'nombre' => 'Nocturna',
            ])
            ->assertRedirect(route('sistemas.crud.jornadas.create'))
            ->assertSessionHasErrors('nombre');
    });

    it('creates a zona', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->post(route('sistemas.crud.zonas.store'), [
                'nombre' => 'Zona 8',
                'descripcion' => 'Guayas',
                'usuario' => 'sistemas',
                'activo' => '1',
            ])
            ->assertRedirect(route('sistemas.crud.zonas.index'))
            ->assertSessionHas('status', 'crud-created');

        $this->assertDatabaseHas('sys_zonas', [
            'nombre' => 'Zona 8',
            'descripcion' => 'Guayas',
            'usuario' => 'sistemas',
            'activo' => 1,
        ]);
    });

    it('creates a distrito for an existing zona', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $zona = Sys_Zona::factory()->create();

        $this->actingAs($actor)
            ->post(route('sistemas.crud.distritos.store'), [
                'nombre' => '09D01',
                'provincia' => 'Guayas',
                'descripcion' => 'Distrito norte',
                'usuario' => 'sistemas',
                'activo' => '1',
                'zona_id' => $zona->id,
            ])
            ->assertRedirect(route('sistemas.crud.distritos.index'))
            ->assertSessionHas('status', 'crud-created');

        $this->assertDatabaseHas('sys_distritos', [
            'nombre' => '09D01',
            'provincia' => 'Guayas',
            'zona_id' => $zona->id,
        ]);
    });

    it('rejects a distrito without a zona', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->from(route('sistemas.crud.distritos.create'))
            ->post(route('sistemas.crud.distritos.store'), [
                'nombre' => '09D02',
                'provincia' => 'Guayas',
                'descripcion' => 'Sin zona',
                'usuario' => 'sistemas',
                'activo' => '1',
            ])
            ->assertRedirect(route('sistemas.crud.distritos.create'))
            ->assertSessionHasErrors('zona_id');

        $this->assertDatabaseMissing('sys_distritos', ['nombre' => '09D02']);
    });

    it('creates a circuito for an existing distrito', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $distrito = Sys_Distrito::factory()->create();

        $this->actingAs($actor)
            ->post(route('sistemas.crud.circuitos.store'), [
                'nombre' => '09D01C01',
                'descripcion' => 'Circuito centro',
                'usuario' => 'sistemas',
                'activo' => '1',
                'distrito_id' => $distrito->id,
            ])
            ->assertRedirect(route('sistemas.crud.circuitos.index'))
            ->assertSessionHas('status', 'crud-created');

        $this->assertDatabaseHas('sys_circuitos', [
            'nombre' => '09D01C01',
            'distrito_id' => $distrito->id,
        ]);
    });

    it('forbids administrators from creating records', function () {
        $actor = assignRole(User::factory()->create(), Role::Admin);

        $this->actingAs($actor)
            ->post(route('sistemas.crud.jornadas.store'), [
                'nombre' => 'Intrusa',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('sys_jornadas', ['nombre' => 'Intrusa']);
    });
});

describe('update', function () {
    it('updates a jornada', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $jornada = Sys_Jornada::factory()->create(['nombre' => 'Vieja']);

        $this->actingAs($actor)
            ->patch(route('sistemas.crud.jornadas.update', $jornada), [
                'nombre' => 'Nueva',
                'descripcion' => 'Actualizada',
            ])
            ->assertRedirect(route('sistemas.crud.jornadas.index'))
            ->assertSessionHas('status', 'crud-updated');

        expect($jornada->fresh()->nombre)->toBe('Nueva')
            ->and($jornada->fresh()->descripcion)->toBe('Actualizada');
    });
});

describe('destroy', function () {
    it('deletes a jornada', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $jornada = Sys_Jornada::factory()->create();

        $this->actingAs($actor)
            ->delete(route('sistemas.crud.jornadas.destroy', $jornada))
            ->assertRedirect(route('sistemas.crud.jornadas.index'))
            ->assertSessionHas('status', 'crud-deleted');

        $this->assertModelMissing($jornada);
    });
});

describe('edit', function () {
    it('shows the selected jornada in the edit form', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $jornada = Sys_Jornada::factory()->create(['nombre' => 'Intensiva']);

        $this->actingAs($actor)
            ->get(route('sistemas.crud.jornadas.edit', $jornada))
            ->assertOk()
            ->assertSee('Intensiva')
            ->assertSee('Editar jornada');
    });
});

it('shows the catalogos hub to systems users after seeding the menu', function () {
    $this->seed(NavigationSeeder::class);
    $user = assignRole(User::factory()->create(), Role::Sistemas);
    $catalogos = NavigationItem::query()->where('label', 'Catálogos')->firstOrFail();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee(route('navigation.hub', $catalogos), false);
});

it('seeds geographic catalogs under catalogos and omits establecimientos', function () {
    $this->seed(NavigationSeeder::class);
    $user = assignRole(User::factory()->create(), Role::Sistemas);
    $catalogos = NavigationItem::query()->where('label', 'Catálogos')->firstOrFail();

    $this->actingAs($user)
        ->get(route('navigation.hub', $catalogos))
        ->assertOk()
        ->assertSee('Zonas')
        ->assertSee('Distritos')
        ->assertSee('Circuitos')
        ->assertSee(route('sistemas.crud.zonas.index'), false)
        ->assertSee(route('sistemas.crud.distritos.index'), false)
        ->assertSee(route('sistemas.crud.circuitos.index'), false)
        ->assertDontSee('Establecimientos');
});
