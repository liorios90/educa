<?php

use App\Enums\Role;
use App\Models\NavigationItem;
use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

describe('index', function () {
    it('allows systems users to open the nested curriculo screen', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create(['nombre' => 'Educación General Básica']);
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create(['nombre' => 'Básica Superior']);
        $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create(['nombre' => 'Lengua y Literatura']);
        Sys_Asignatura::factory()->for($area, 'area')->create(['nombre' => 'Lengua y Literatura']);

        $this->actingAs($user)
            ->get(route('sistemas.curriculo'))
            ->assertOk()
            ->assertSee('Áreas y asignaturas')
            ->assertSee('Educación General Básica')
            ->assertSee('Básica Superior')
            ->assertSee('Lengua y Literatura')
            ->assertSee('Crear área')
            ->assertSee('Crear asignatura')
            ->assertSee('En las libretas del Ministerio de Educación')
            ->assertSee(route('sistemas.estructura'), false);
    });

    it('forbids administrators from creating an area', function () {
        $user = assignRole(User::factory()->create(), Role::Admin);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();

        $this->actingAs($user)
            ->post(route('sistemas.curriculo.areas.store', [$nivel, $subnivel]), [
                'nombre' => 'Matemática',
            ])
            ->assertForbidden();
    });

    it('forbids administrators from opening curriculo', function () {
        $user = assignRole(User::factory()->create(), Role::Admin);

        $this->actingAs($user)
            ->get(route('sistemas.curriculo'))
            ->assertForbidden();
    });

    it('redirects guests from curriculo to login', function () {
        $this->get(route('sistemas.curriculo'))
            ->assertRedirect(route('login'));
    });

    it('escapes field values in the curriculo tree', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create([
            'nombre' => "<script>alert('nivel')</script>",
        ]);
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create([
            'nombre' => "<script>alert('subnivel')</script>",
        ]);
        $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create([
            'nombre' => "<script>alert('area')</script>",
        ]);
        Sys_Asignatura::factory()->for($area, 'area')->create([
            'nombre' => "<script>alert('asignatura')</script>",
        ]);

        $this->actingAs($user)
            ->get(route('sistemas.curriculo'))
            ->assertSee("<script>alert('nivel')</script>")
            ->assertDontSee("<script>alert('nivel')</script>", false)
            ->assertSee("<script>alert('subnivel')</script>")
            ->assertDontSee("<script>alert('subnivel')</script>", false)
            ->assertSee("<script>alert('area')</script>")
            ->assertDontSee("<script>alert('area')</script>", false)
            ->assertSee("<script>alert('asignatura')</script>")
            ->assertDontSee("<script>alert('asignatura')</script>", false);
    });

    it('shows a back link to the parent hub when the menu item exists', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $group = NavigationItem::factory()->group()->create([
            'label' => 'Estructura',
            'visible_to_all' => false,
        ]);
        $group->roles()->sync($user->roles->pluck('id'));
        NavigationItem::factory()->childOf($group)->create([
            'label' => 'Áreas y asignaturas',
            'route_name' => 'sistemas.curriculo',
        ]);

        $this->actingAs($user)
            ->get(route('sistemas.curriculo'))
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
            'label' => 'Áreas y asignaturas',
            'route_name' => 'sistemas.curriculo',
        ]);

        $this->actingAs($user)
            ->get(route('sistemas.curriculo'))
            ->assertOk()
            ->assertDontSee('Volver')
            ->assertDontSee(route('navigation.hub', $group), false);
    });
});

describe('areas', function () {
    it('creates an area for the selected subnivel', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();

        $response = $this->actingAs($actor)
            ->post(route('sistemas.curriculo.areas.store', [$nivel, $subnivel]), [
                'nombre' => 'Matemática',
                'descripcion' => 'Área de matemática',
                'orden' => 2,
                'aparece_en_libreta' => '1',
            ]);

        $area = Sys_Area::query()->where('nombre', 'Matemática')->firstOrFail();

        $response
            ->assertRedirect(route('sistemas.curriculo', [
                'nivel' => $nivel->id,
                'subnivel' => $subnivel->id,
                'area' => $area->id,
            ]))
            ->assertSessionHas('status', 'area-created');

        $this->assertDatabaseHas('sys_areas', [
            'id' => $area->id,
            'nombre' => 'Matemática',
            'descripcion' => 'Área de matemática',
            'orden' => 2,
            'aparece_en_libreta' => true,
            'subnivel_id' => $subnivel->id,
        ]);
    });

    it('rejects an empty area payload', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();

        $this->actingAs($actor)
            ->from(route('sistemas.curriculo', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id]))
            ->post(route('sistemas.curriculo.areas.store', [$nivel, $subnivel]), [])
            ->assertRedirect(route('sistemas.curriculo', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id]))
            ->assertSessionHasErrorsIn('area-create-'.$subnivel->id, 'nombre');
    });

    it('rejects a duplicate area nombre in the same subnivel', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        Sys_Area::factory()->for($subnivel, 'subnivel')->create(['nombre' => 'Matemática']);

        $this->actingAs($actor)
            ->from(route('sistemas.curriculo', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id]))
            ->post(route('sistemas.curriculo.areas.store', [$nivel, $subnivel]), [
                'nombre' => 'Matemática',
            ])
            ->assertRedirect(route('sistemas.curriculo', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id]))
            ->assertSessionHasErrorsIn('area-create-'.$subnivel->id, 'nombre');
    });

    it('allows the same area nombre in another subnivel', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $otroSubnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        Sys_Area::factory()->for($subnivel, 'subnivel')->create(['nombre' => 'Matemática']);

        $this->actingAs($actor)
            ->post(route('sistemas.curriculo.areas.store', [$nivel, $otroSubnivel]), [
                'nombre' => 'Matemática',
                'aparece_en_libreta' => '1',
            ])
            ->assertSessionHas('status', 'area-created');

        expect(Sys_Area::query()->where('nombre', 'Matemática')->count())->toBe(2);
    });

    it('updates an area', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create(['nombre' => 'Ciencias']);

        $this->actingAs($actor)
            ->patch(route('sistemas.curriculo.areas.update', [$nivel, $subnivel, $area]), [
                'nombre' => 'Ciencias Naturales',
                'descripcion' => 'Actualizado',
                'orden' => 3,
                'aparece_en_libreta' => '0',
            ])
            ->assertRedirect(route('sistemas.curriculo', [
                'nivel' => $nivel->id,
                'subnivel' => $subnivel->id,
                'area' => $area->id,
            ]))
            ->assertSessionHas('status', 'area-updated');

        expect($area->fresh())
            ->nombre->toBe('Ciencias Naturales')
            ->aparece_en_libreta->toBeFalse();
    });

    it('does not update an area from another subnivel', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $otroSubnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create();

        $this->actingAs($actor)
            ->patch(route('sistemas.curriculo.areas.update', [$nivel, $otroSubnivel, $area]), [
                'nombre' => 'No pertenece',
            ])
            ->assertNotFound();
    });

    it('deletes an area without asignaturas', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create();

        $this->actingAs($actor)
            ->delete(route('sistemas.curriculo.areas.destroy', [$nivel, $subnivel, $area]))
            ->assertRedirect(route('sistemas.curriculo', [
                'nivel' => $nivel->id,
                'subnivel' => $subnivel->id,
            ]))
            ->assertSessionHas('status', 'area-deleted');

        $this->assertModelMissing($area);
    });

    it('does not delete an area that has asignaturas', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create();
        Sys_Asignatura::factory()->for($area, 'area')->create();

        $this->actingAs($actor)
            ->delete(route('sistemas.curriculo.areas.destroy', [$nivel, $subnivel, $area]))
            ->assertRedirect(route('sistemas.curriculo', [
                'nivel' => $nivel->id,
                'subnivel' => $subnivel->id,
                'area' => $area->id,
            ]))
            ->assertSessionHas('error', 'No se puede eliminar el área porque tiene asignaturas asociadas.');

        $this->assertModelExists($area);
    });
});

describe('asignaturas', function () {
    it('creates an asignatura for the selected area', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create();

        $this->actingAs($actor)
            ->post(route('sistemas.curriculo.asignaturas.store', [$nivel, $subnivel, $area]), [
                'nombre' => 'Álgebra',
                'descripcion' => 'Álgebra de básica superior',
                'orden' => 1,
                'horas_semanales' => 5,
                'aparece_en_libreta' => '1',
            ])
            ->assertRedirect(route('sistemas.curriculo', [
                'nivel' => $nivel->id,
                'subnivel' => $subnivel->id,
                'area' => $area->id,
            ]))
            ->assertSessionHas('status', 'asignatura-created');

        $this->assertDatabaseHas('sys_asignaturas', [
            'nombre' => 'Álgebra',
            'descripcion' => 'Álgebra de básica superior',
            'orden' => 1,
            'horas_semanales' => 5,
            'aparece_en_libreta' => true,
            'area_id' => $area->id,
        ]);
    });

    it('rejects an empty asignatura payload', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create();

        $this->actingAs($actor)
            ->from(route('sistemas.curriculo', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id, 'area' => $area->id]))
            ->post(route('sistemas.curriculo.asignaturas.store', [$nivel, $subnivel, $area]), [])
            ->assertRedirect(route('sistemas.curriculo', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id, 'area' => $area->id]))
            ->assertSessionHasErrorsIn('asignatura-create-'.$area->id, 'nombre');
    });

    it('rejects a duplicate asignatura nombre in the same area', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create();
        Sys_Asignatura::factory()->for($area, 'area')->create(['nombre' => 'Álgebra']);

        $this->actingAs($actor)
            ->from(route('sistemas.curriculo', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id, 'area' => $area->id]))
            ->post(route('sistemas.curriculo.asignaturas.store', [$nivel, $subnivel, $area]), [
                'nombre' => 'Álgebra',
            ])
            ->assertRedirect(route('sistemas.curriculo', ['nivel' => $nivel->id, 'subnivel' => $subnivel->id, 'area' => $area->id]))
            ->assertSessionHasErrorsIn('asignatura-create-'.$area->id, 'nombre');
    });

    it('updates an asignatura', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create();
        $asignatura = Sys_Asignatura::factory()->for($area, 'area')->create(['nombre' => 'Geometría']);

        $this->actingAs($actor)
            ->patch(route('sistemas.curriculo.asignaturas.update', [$nivel, $subnivel, $area, $asignatura]), [
                'nombre' => 'Geometría y medida',
                'descripcion' => 'Actualizado',
                'orden' => 4,
                'horas_semanales' => 3,
                'aparece_en_libreta' => '1',
            ])
            ->assertRedirect(route('sistemas.curriculo', [
                'nivel' => $nivel->id,
                'subnivel' => $subnivel->id,
                'area' => $area->id,
            ]))
            ->assertSessionHas('status', 'asignatura-updated');

        expect($asignatura->fresh()->nombre)->toBe('Geometría y medida');
    });

    it('does not update an asignatura from another area', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create();
        $otraArea = Sys_Area::factory()->for($subnivel, 'subnivel')->create();
        $asignatura = Sys_Asignatura::factory()->for($area, 'area')->create();

        $this->actingAs($actor)
            ->patch(route('sistemas.curriculo.asignaturas.update', [$nivel, $subnivel, $otraArea, $asignatura]), [
                'nombre' => 'No pertenece',
            ])
            ->assertNotFound();
    });

    it('deletes an asignatura', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $nivel = Sys_Nivel::factory()->create();
        $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create();
        $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create();
        $asignatura = Sys_Asignatura::factory()->for($area, 'area')->create();

        $this->actingAs($actor)
            ->delete(route('sistemas.curriculo.asignaturas.destroy', [$nivel, $subnivel, $area, $asignatura]))
            ->assertRedirect(route('sistemas.curriculo', [
                'nivel' => $nivel->id,
                'subnivel' => $subnivel->id,
                'area' => $area->id,
            ]))
            ->assertSessionHas('status', 'asignatura-deleted');

        $this->assertModelMissing($asignatura);
    });
});

it('seeds the curriculo hub button for systems users', function () {
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
        ->assertSee('Áreas y asignaturas')
        ->assertSee(route('sistemas.curriculo'), false);
});
