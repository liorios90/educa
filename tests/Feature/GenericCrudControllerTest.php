<?php

use App\Enums\Role;
use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
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

it('shows the jornadas link to systems users after seeding the menu', function () {
    $this->seed(NavigationSeeder::class);
    $user = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee(route('sistemas.crud.jornadas.index'), false);
});
