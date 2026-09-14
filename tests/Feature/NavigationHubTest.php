<?php

use App\Enums\Role;
use App\Models\NavigationItem;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

it('shows group buttons on the hub and hides children from the sidebar', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);
    $group = NavigationItem::factory()->group()->create([
        'label' => 'Catálogos',
        'visible_to_all' => false,
    ]);
    $group->roles()->sync($actor->roles->pluck('id'));

    $child = NavigationItem::factory()->childOf($group)->create([
        'label' => 'Jornadas',
        'route_name' => 'sistemas.crud.jornadas.index',
    ]);
    $child->copyVisibilityFrom($group->fresh(['roles']));

    $this->actingAs($actor)
        ->get(route('dashboard'))
        ->assertSee('Catálogos')
        ->assertSee(route('navigation.hub', $group), false)
        ->assertDontSee(route('sistemas.crud.jornadas.index'), false);

    $this->actingAs($actor)
        ->get(route('navigation.hub', $group))
        ->assertOk()
        ->assertSee('Catálogos')
        ->assertSee('Jornadas')
        ->assertSee(route('sistemas.crud.jornadas.index'), false);
});

it('does not show inactive children as hub buttons', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);
    $group = NavigationItem::factory()->group()->create(['visible_to_all' => true]);
    NavigationItem::factory()->childOf($group)->inactive()->create([
        'label' => 'Oculto',
        'route_name' => 'dashboard',
        'visible_to_all' => true,
    ]);

    $this->actingAs($actor)
        ->get(route('navigation.hub', $group))
        ->assertDontSee('Oculto');
});

it('returns 404 when a user cannot see the hub', function () {
    $admin = assignRole(User::factory()->create(), Role::Admin);
    $sistemas = assignRole(User::factory()->create(), Role::Sistemas);
    $group = NavigationItem::factory()->group()->create(['visible_to_all' => false]);
    $group->roles()->sync($sistemas->roles->pluck('id'));

    $this->actingAs($admin)
        ->get(route('navigation.hub', $group))
        ->assertNotFound();
});

it('escapes submenu labels on the hub', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);
    $group = NavigationItem::factory()->group()->create(['visible_to_all' => true]);
    NavigationItem::factory()->childOf($group)->create([
        'label' => "<script>alert('xss')</script>",
        'route_name' => 'dashboard',
        'visible_to_all' => true,
    ]);

    $this->actingAs($actor)
        ->get(route('navigation.hub', $group))
        ->assertSee("<script>alert('xss')</script>")
        ->assertDontSee("<script>alert('xss')</script>", false);
});

it('lets systems users manage submenus of a group', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);
    $group = NavigationItem::factory()->group()->create(['visible_to_all' => false]);
    $group->roles()->sync($actor->roles->pluck('id'));

    $this->actingAs($actor)
        ->post(route('sistemas.navigation-items.submenus.store', $group), [
            'label' => 'Modalidades',
            'route_name' => 'sistemas.crud.modalidades.index',
            'icon' => 'cog',
            'sort_order' => 1,
            'is_active' => '1',
        ])
        ->assertRedirect(route('sistemas.navigation-items.submenus.index', $group))
        ->assertSessionHas('status', 'navigation-submenu-created');

    $this->assertDatabaseHas('navigation_items', [
        'label' => 'Modalidades',
        'parent_id' => $group->id,
        'is_group' => false,
    ]);

    $submenu = NavigationItem::query()->where('label', 'Modalidades')->firstOrFail();

    $this->actingAs($actor)
        ->patch(route('sistemas.navigation-items.submenus.update', [$group, $submenu]), [
            'label' => 'Modalidad',
            'route_name' => 'sistemas.crud.modalidades.index',
            'icon' => 'cog',
            'sort_order' => 2,
            'is_active' => '1',
        ])
        ->assertRedirect(route('sistemas.navigation-items.submenus.index', $group));

    expect($submenu->fresh()->label)->toBe('Modalidad');

    $this->actingAs($actor)
        ->delete(route('sistemas.navigation-items.submenus.destroy', [$group, $submenu]))
        ->assertRedirect(route('sistemas.navigation-items.submenus.index', $group));

    $this->assertModelMissing($submenu);
});

it('does not list children on the top-level menu manager', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);
    $group = NavigationItem::factory()->group()->create(['label' => 'Padre']);
    NavigationItem::factory()->childOf($group)->create(['label' => 'Hijo']);

    $this->actingAs($actor)
        ->get(route('sistemas.navigation-items.index'))
        ->assertSee('Padre')
        ->assertSee('Submenús')
        ->assertDontSee('Hijo');
});

it('forbids administrators from managing submenus', function () {
    $actor = assignRole(User::factory()->create(), Role::Admin);
    $group = NavigationItem::factory()->group()->create();

    $this->actingAs($actor)
        ->get(route('sistemas.navigation-items.submenus.index', $group))
        ->assertForbidden();
});

it('deletes children when the parent group is deleted', function () {
    $actor = assignRole(User::factory()->create(), Role::Sistemas);
    $group = NavigationItem::factory()->group()->create();
    $child = NavigationItem::factory()->childOf($group)->create();

    $this->actingAs($actor)
        ->delete(route('sistemas.navigation-items.destroy', $group))
        ->assertRedirect(route('sistemas.navigation-items.index'));

    $this->assertModelMissing($group);
    $this->assertModelMissing($child);
});

it('shows catalogos in the sidebar after seeding', function () {
    $this->seed(NavigationSeeder::class);
    $user = assignRole(User::factory()->create(), Role::Sistemas);
    $catalogos = NavigationItem::query()->where('label', 'Catálogos')->firstOrFail();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('Catálogos')
        ->assertSee(route('navigation.hub', $catalogos), false)
        ->assertDontSee(route('sistemas.crud.jornadas.index'), false);
});
