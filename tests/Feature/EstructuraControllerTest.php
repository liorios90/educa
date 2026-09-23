<?php

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\NavigationItem;
use App\Models\Sys_Area;
use App\Models\Sys_Grado;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

describe('index', function () {
    it('allows systems users to open the nested estructura screen', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create(['nombre' => 'Educación General Básica']);
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create(['nombre' => 'Básica Superior']);
        Sys_Grado::factory()->for($subnivel, 'subnivel')->create(['nombre' => '8vo Básica']);

        $this->actingAs($user)
            ->get(route('sistemas.estructura'))
            ->assertOk()
            ->assertSee('Niveles, subniveles y grados')
            ->assertSee('Nuevo nivel')
            ->assertSee('Educación General Básica')
            ->assertSee('Básica Superior')
            ->assertSee('8vo Básica')
            ->assertSee('Crear subnivel')
            ->assertSee('Crear grado');
    });

    it('forbids administrators from creating a nivel', function () {
        $user = assignRole(User::factory()->create(), Role::Admin);

        $this->actingAs($user)
            ->post(route('sistemas.estructura.niveles.store'), [
                'nombre' => 'Bachillerato',
            ])
            ->assertForbidden();
    });

    it('forbids administrators from opening estructura', function () {
        $user = assignRole(User::factory()->create(), Role::Admin);

        $this->actingAs($user)
            ->get(route('sistemas.estructura'))
            ->assertForbidden();
    });

    it('redirects guests from estructura to login', function () {
        $this->get(route('sistemas.estructura'))
            ->assertRedirect(route('login'));
    });

    it('escapes field values in the estructura tree', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create([
            'nombre' => "<script>alert('nivel')</script>",
        ]);
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create([
            'nombre' => "<script>alert('subnivel')</script>",
        ]);
        Sys_Grado::factory()->for($subnivel, 'subnivel')->create([
            'nombre' => "<script>alert('grado')</script>",
        ]);

        $this->actingAs($user)
            ->get(route('sistemas.estructura'))
            ->assertSee("<script>alert('nivel')</script>")
            ->assertDontSee("<script>alert('nivel')</script>", false)
            ->assertSee("<script>alert('subnivel')</script>")
            ->assertDontSee("<script>alert('subnivel')</script>", false)
            ->assertSee("<script>alert('grado')</script>")
            ->assertDontSee("<script>alert('grado')</script>", false);
    });

    it('shows a back link to the parent hub when the menu item exists', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $group = NavigationItem::factory()->group()->create([
            'label' => 'Estructura',
            'visible_to_all' => false,
        ]);
        $group->roles()->sync($user->roles->pluck('id'));
        NavigationItem::factory()->childOf($group)->create([
            'label' => 'Nivel - Subnivel - Grado',
            'route_name' => 'sistemas.estructura',
        ]);

        $this->actingAs($user)
            ->get(route('sistemas.estructura'))
            ->assertOk()
            ->assertSee('Volver')
            ->assertSee(route('navigation.hub', $group), false);
    });

    it('hides the back link when the parent is a sidebar submenu', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $group = NavigationItem::factory()->sidebar()->create([
            'label' => 'Estructura',
            'visible_to_all' => false,
        ]);
        $group->roles()->sync($user->roles->pluck('id'));
        NavigationItem::factory()->childOf($group)->create([
            'label' => 'Nivel - Subnivel - Grado',
            'route_name' => 'sistemas.estructura',
        ]);

        $this->actingAs($user)
            ->get(route('sistemas.estructura'))
            ->assertOk()
            ->assertDontSee('Volver')
            ->assertDontSee(route('navigation.hub', $group), false);
    });
});

describe('niveles', function () {
    it('creates a nivel and redirects so subniveles can be added', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $response = $this->actingAs($actor)
            ->post(route('sistemas.estructura.niveles.store'), [
                'nombre' => 'Bachillerato',
                'descripcion' => 'Nivel de bachillerato',
            ]);

        $nivel = Sys_Nivel::query()->where('nombre', 'Bachillerato')->firstOrFail();

        $response
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id]))
            ->assertSessionHas('status', 'nivel-created');

        $this->assertDatabaseHas('sys_niveles', [
            'id' => $nivel->id,
            'nombre' => 'Bachillerato',
            'descripcion' => 'Nivel de bachillerato',
        ]);
    });

    it('rejects an empty nivel payload', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->from(route('sistemas.estructura'))
            ->post(route('sistemas.estructura.niveles.store'), [])
            ->assertRedirect(route('sistemas.estructura'))
            ->assertSessionHasErrorsIn('nivel-create', 'nombre');
    });

    it('rejects a duplicate nivel nombre', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        Sys_Nivel::factory()->create(['nombre' => 'Bachillerato']);

        $this->actingAs($actor)
            ->from(route('sistemas.estructura'))
            ->post(route('sistemas.estructura.niveles.store'), [
                'nombre' => 'Bachillerato',
            ])
            ->assertRedirect(route('sistemas.estructura'))
            ->assertSessionHasErrorsIn('nivel-create', 'nombre');
    });

    it('updates a nivel', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create(['nombre' => 'Básica']);

        $this->actingAs($actor)
            ->patch(route('sistemas.estructura.niveles.update', $nivel), [
                'nombre' => 'Educación Básica',
                'descripcion' => 'Actualizado',
            ])
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id]))
            ->assertSessionHas('status', 'nivel-updated');

        expect($nivel->fresh()->nombre)->toBe('Educación Básica');
    });

    it('deletes a nivel without subniveles', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();

        $this->actingAs($actor)
            ->delete(route('sistemas.estructura.niveles.destroy', $nivel))
            ->assertRedirect(route('sistemas.estructura'))
            ->assertSessionHas('status', 'nivel-deleted');

        $this->assertModelMissing($nivel);
    });

    it('does not delete a nivel that has subniveles', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        Sys_Subnivel::factory()->for($nivel, 'nivel')->create();

        $this->actingAs($actor)
            ->from(route('sistemas.estructura'))
            ->delete(route('sistemas.estructura.niveles.destroy', $nivel))
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id]))
            ->assertSessionHas('error', 'No se puede eliminar el nivel porque tiene subniveles asociados.');

        $this->assertModelExists($nivel);
    });

    it('does not delete a nivel that is used by an establishment', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $establecimiento = Establecimiento::factory()->create();
        $establecimiento->niveles()->attach($nivel->id);

        $this->actingAs($actor)
            ->from(route('sistemas.estructura'))
            ->delete(route('sistemas.estructura.niveles.destroy', $nivel))
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id]))
            ->assertSessionHas('error', 'No se puede eliminar el nivel porque está en uso por un establecimiento.');

        $this->assertModelExists($nivel);
    });
});

describe('subniveles', function () {
    it('creates a subnivel for the selected nivel', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();

        $response = $this->actingAs($actor)
            ->post(route('sistemas.estructura.subniveles.store', $nivel), [
                'nombre' => 'Básica Superior',
                'descripcion' => 'Octavo a décimo',
            ]);

        $subnivel = Sys_Subnivel::query()->where('nombre', 'Básica Superior')->firstOrFail();

        $response
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id]))
            ->assertSessionHas('status', 'subnivel-created');

        expect($subnivel->nivel_id)->toBe($nivel->id);
    });

    it('rejects an empty subnivel payload', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();

        $this->actingAs($actor)
            ->from(route('sistemas.estructura', ['nivel' => $nivel->id]))
            ->post(route('sistemas.estructura.subniveles.store', $nivel), [])
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id]))
            ->assertSessionHasErrorsIn('subnivel-create-'.$nivel->id, 'nombre');
    });

    it('updates a subnivel', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create(['nombre' => 'Superior']);

        $this->actingAs($actor)
            ->patch(route('sistemas.estructura.subniveles.update', [$nivel, $subnivel]), [
                'nombre' => 'Básica Superior',
                'descripcion' => 'Actualizado',
            ])
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id]))
            ->assertSessionHas('status', 'subnivel-updated');

        expect($subnivel->fresh()->nombre)->toBe('Básica Superior');
    });

    it('does not update a subnivel from another nivel', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $otroNivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();

        $this->actingAs($actor)
            ->patch(route('sistemas.estructura.subniveles.update', [$otroNivel, $subnivel]), [
                'nombre' => 'No pertenece',
            ])
            ->assertNotFound();
    });

    it('deletes a subnivel without grados', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();

        $this->actingAs($actor)
            ->delete(route('sistemas.estructura.subniveles.destroy', [$nivel, $subnivel]))
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id]))
            ->assertSessionHas('status', 'subnivel-deleted');

        $this->assertModelMissing($subnivel);
    });

    it('does not delete a subnivel that has grados', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        Sys_Grado::factory()->for($subnivel, 'subnivel')->create();

        $this->actingAs($actor)
            ->delete(route('sistemas.estructura.subniveles.destroy', [$nivel, $subnivel]))
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id]))
            ->assertSessionHas('error', 'No se puede eliminar el subnivel porque tiene grados asociados.');

        $this->assertModelExists($subnivel);
    });

    it('does not delete a subnivel that has areas', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        Sys_Area::factory()->for($subnivel, 'subnivel')->create();

        $this->actingAs($actor)
            ->delete(route('sistemas.estructura.subniveles.destroy', [$nivel, $subnivel]))
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id]))
            ->assertSessionHas('error', 'No se puede eliminar el subnivel porque tiene áreas asociadas.');

        $this->assertModelExists($subnivel);
    });

    it('does not delete a subnivel that is used by an establishment', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $establecimiento = Establecimiento::factory()->create();
        $establecimiento->subniveles()->attach($subnivel->id, ['nivel_id' => $subnivel->nivel_id]);

        $this->actingAs($actor)
            ->delete(route('sistemas.estructura.subniveles.destroy', [$nivel, $subnivel]))
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id]))
            ->assertSessionHas('error', 'No se puede eliminar el subnivel porque está en uso por un establecimiento.');

        $this->assertModelExists($subnivel);
    });
});

describe('grados', function () {
    it('creates a grado for the selected subnivel', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();

        $this->actingAs($actor)
            ->post(route('sistemas.estructura.grados.store', [$nivel, $subnivel]), [
                'nombre' => '8vo Básica',
                'descripcion' => 'Octavo año',
            ])
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id]))
            ->assertSessionHas('status', 'grado-created');

        $this->assertDatabaseHas('sys_grados', [
            'nombre' => '8vo Básica',
            'descripcion' => 'Octavo año',
            'subnivel_id' => $subnivel->id,
        ]);
    });

    it('rejects an empty grado payload', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();

        $this->actingAs($actor)
            ->from(route('sistemas.estructura', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id]))
            ->post(route('sistemas.estructura.grados.store', [$nivel, $subnivel]), [])
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id]))
            ->assertSessionHasErrorsIn('grado-create-'.$subnivel->id, 'nombre');
    });

    it('updates a grado', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $grado = Sys_Grado::factory()->for($subnivel, 'subnivel')->create(['nombre' => '8vo']);

        $this->actingAs($actor)
            ->patch(route('sistemas.estructura.grados.update', [$nivel, $subnivel, $grado]), [
                'nombre' => '8vo Básica',
                'descripcion' => 'Actualizado',
            ])
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id]))
            ->assertSessionHas('status', 'grado-updated');

        expect($grado->fresh()->nombre)->toBe('8vo Básica');
    });

    it('does not update a grado from another subnivel', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $otroSubnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $grado = Sys_Grado::factory()->for($subnivel, 'subnivel')->create();

        $this->actingAs($actor)
            ->patch(route('sistemas.estructura.grados.update', [$nivel, $otroSubnivel, $grado]), [
                'nombre' => 'No pertenece',
            ])
            ->assertNotFound();
    });

    it('deletes a grado', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $grado = Sys_Grado::factory()->for($subnivel, 'subnivel')->create();

        $this->actingAs($actor)
            ->delete(route('sistemas.estructura.grados.destroy', [$nivel, $subnivel, $grado]))
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id]))
            ->assertSessionHas('status', 'grado-deleted');

        $this->assertModelMissing($grado);
    });

    it('does not delete a grado that is used by an establishment', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $grado = Sys_Grado::factory()->for($subnivel, 'subnivel')->create();
        $establecimiento = Establecimiento::factory()->create();
        $establecimiento->grados()->attach($grado->id, ['subnivel_id' => $grado->subnivel_id]);

        $this->actingAs($actor)
            ->delete(route('sistemas.estructura.grados.destroy', [$nivel, $subnivel, $grado]))
            ->assertRedirect(route('sistemas.estructura', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id]))
            ->assertSessionHas('error', 'No se puede eliminar el grado porque está en uso por un establecimiento.');

        $this->assertModelExists($grado);
    });
});

it('seeds the estructura hub button for systems users', function () {
    $this->seed(NavigationSeeder::class);
    $user = assignRole(User::factory()->create(), Role::Sistemas);
    $estructura = NavigationItem::query()
        ->where('label', 'Estructura')
        ->where('route_name', 'navigation.hub')
        ->whereNull('parent_id')
        ->firstOrFail();

    $this->actingAs($user)
        ->get(route('navigation.hub', $estructura))
        ->assertOk()
        ->assertSee('Nivel - Subnivel - Grado')
        ->assertSee('Áreas y asignaturas')
        ->assertSee(route('sistemas.estructura'), false)
        ->assertSee(route('sistemas.curriculo'), false);
});
