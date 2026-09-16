<?php

use App\Enums\Role;
use App\Models\NavigationItem;
use App\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

describe('index', function () {
    it('allows systems users to open the menu manager', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index'))
            ->assertSee('Opciones de menú')
            ->assertSee('Nueva opción');
    });

    it('forbids administrators from opening the menu manager', function () {
        $user = assignRole(User::factory()->create(), Role::Admin);

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index'))
            ->assertForbidden();
    });

    it('redirects guests from the menu manager to login', function () {
        $this->get(route('sistemas.navigation-items.index'))
            ->assertRedirect(route('login'));
    });

    it('escapes labels in the menu list', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        NavigationItem::factory()->create([
            'label' => "<script>alert('xss')</script>",
        ]);

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index'))
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });

    it('paginates menu options and keeps later items on the next page', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        foreach (range(1, 16) as $number) {
            NavigationItem::factory()->create([
                'sort_order' => $number,
                'label' => 'Opcion '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            ]);
        }

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index'))
            ->assertSee('Opcion 01')
            ->assertSee('Opcion 15')
            ->assertDontSee('Opcion 16')
            ->assertSee('page=2', false);

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', ['page' => 2]))
            ->assertSee('Opcion 16')
            ->assertDontSee('Opcion 01');
    });

    it('sorts menu options by the selected column', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        NavigationItem::factory()->create(['label' => 'Zorro', 'sort_order' => 1]);
        NavigationItem::factory()->create(['label' => 'Abeja', 'sort_order' => 2]);

        $html = $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', [
                'sort' => 'label',
                'direction' => 'asc',
            ]))
            ->assertOk()
            ->assertSee('sort=label', false)
            ->getContent();

        expect(strpos($html, 'Abeja'))->toBeLessThan(strpos($html, 'Zorro'));
    });

    it('falls back to the default order when the sort query is invalid', function (array $query) {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        NavigationItem::factory()->create(['label' => 'Zorro', 'sort_order' => 1]);
        NavigationItem::factory()->create(['label' => 'Abeja', 'sort_order' => 2]);

        $html = $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', $query))
            ->assertOk()
            ->getContent();

        expect(strpos($html, 'Zorro'))->toBeLessThan(strpos($html, 'Abeja'));
    })->with([
        'unknown column' => [['sort' => 'password', 'direction' => 'asc']],
        'sql fragment' => [['sort' => 'label;drop table navigation_items', 'direction' => 'asc']],
        'invalid direction' => [['sort' => 'sort_order', 'direction' => 'sideways']],
    ]);

    it('filters menu options by the search term', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        NavigationItem::factory()->create(['label' => 'Reportes', 'route_name' => 'dashboard']);
        NavigationItem::factory()->create(['label' => 'Ayuda', 'route_name' => 'profile.edit']);

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', ['q' => 'Repo']))
            ->assertOk()
            ->assertSee('Reportes')
            ->assertDontSee('Ayuda')
            ->assertSee('name="q"', false)
            ->assertSee('Limpiar');
    });

    it('finds menu options by route name', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        NavigationItem::factory()->create(['label' => 'Reportes', 'route_name' => 'reports.index']);
        NavigationItem::factory()->create(['label' => 'Ayuda', 'route_name' => 'dashboard']);

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', ['q' => 'reports.index']))
            ->assertSee('Reportes')
            ->assertDontSee('Ayuda');
    });

    it('keeps the search term when sorting', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        NavigationItem::factory()->create(['label' => 'Reportes']);

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', ['q' => 'Reportes', 'sort' => 'label']))
            ->assertSee('q=Reportes', false)
            ->assertSee('sort=label', false);
    });

    it('does not treat the search term as sql', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        NavigationItem::factory()->create(['label' => 'Ayuda']);

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', ['q' => "' OR label IS NOT NULL --"]))
            ->assertOk()
            ->assertDontSee('Ayuda')
            ->assertSee('No hay opciones que coincidan');
    });

    it('escapes the search term in the menu list', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', ['q' => "<script>alert('xss')</script>"]))
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });
});

describe('store', function () {
    it('creates a menu item that appears for the assigned role', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $adminRole = RoleModel::findOrCreate(Role::Admin->value, 'web');
        $admin = assignRole(User::factory()->create(), Role::Admin);

        $this->actingAs($actor)
            ->post(route('sistemas.navigation-items.store'), [
                'label' => 'Reportes',
                'route_name' => 'dashboard',
                'icon' => 'home',
                'sort_order' => 10,
                'is_active' => '1',
                'visible_to_all' => '0',
                'roles' => [$adminRole->id],
            ])
            ->assertRedirect(route('sistemas.navigation-items.index'))
            ->assertSessionHas('status', 'navigation-item-created');

        $this->assertDatabaseHas('navigation_items', [
            'label' => 'Reportes',
            'route_name' => 'dashboard',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertSee('Reportes');

        $this->actingAs($actor)
            ->get(route('dashboard'))
            ->assertDontSee('Reportes');
    });

    it('shows a visible-to-all item to users without a role', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $viewer = User::factory()->create();

        $this->actingAs($actor)
            ->post(route('sistemas.navigation-items.store'), [
                'label' => 'Ayuda',
                'route_name' => 'dashboard',
                'icon' => 'home',
                'sort_order' => 20,
                'is_active' => '1',
                'visible_to_all' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertSee('Ayuda');
    });

    it('does not show inactive items in the sidebar', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->post(route('sistemas.navigation-items.store'), [
                'label' => 'Oculto',
                'route_name' => 'dashboard',
                'icon' => 'home',
                'sort_order' => 30,
                'is_active' => '0',
                'visible_to_all' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($actor)
            ->get(route('dashboard'))
            ->assertDontSee('Oculto');
    });

    it('rejects an empty payload', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->from(route('sistemas.navigation-items.create'))
            ->post(route('sistemas.navigation-items.store'), [])
            ->assertRedirect(route('sistemas.navigation-items.create'))
            ->assertSessionHasErrors(['label', 'route_name', 'icon', 'sort_order', 'roles']);
    });

    it('rejects a route name that does not exist', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->from(route('sistemas.navigation-items.create'))
            ->post(route('sistemas.navigation-items.store'), [
                'label' => 'Roto',
                'route_name' => 'ruta.inexistente',
                'icon' => 'home',
                'sort_order' => 1,
                'is_active' => '1',
                'visible_to_all' => '1',
            ])
            ->assertRedirect(route('sistemas.navigation-items.create'))
            ->assertSessionHasErrors(['route_name' => 'La ruta indicada no existe.']);
    });

    it('requires roles when the item is not visible to everyone', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->from(route('sistemas.navigation-items.create'))
            ->post(route('sistemas.navigation-items.store'), [
                'label' => 'Privado',
                'route_name' => 'dashboard',
                'icon' => 'home',
                'sort_order' => 1,
                'is_active' => '1',
                'visible_to_all' => '0',
            ])
            ->assertRedirect(route('sistemas.navigation-items.create'))
            ->assertSessionHasErrors('roles');
    });

    it('creates a menu group without a destination route', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $sistemasRole = RoleModel::findOrCreate(Role::Sistemas->value, 'web');

        $this->actingAs($actor)
            ->post(route('sistemas.navigation-items.store'), [
                'label' => 'Catálogos',
                'icon' => 'cog',
                'sort_order' => 9,
                'is_active' => '1',
                'visible_to_all' => '0',
                'is_group' => '1',
                'roles' => [$sistemasRole->id],
            ])
            ->assertRedirect(route('sistemas.navigation-items.index'))
            ->assertSessionHas('status', 'navigation-item-created');

        $this->assertDatabaseHas('navigation_items', [
            'label' => 'Catálogos',
            'is_group' => true,
            'route_name' => 'navigation.hub',
        ]);
    });

    it('forbids administrators from creating menu items', function () {
        $actor = assignRole(User::factory()->create(), Role::Admin);

        $this->actingAs($actor)
            ->post(route('sistemas.navigation-items.store'), [
                'label' => 'Intruso',
                'route_name' => 'dashboard',
                'icon' => 'home',
                'sort_order' => 1,
                'is_active' => '1',
                'visible_to_all' => '1',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('navigation_items', ['label' => 'Intruso']);
    });
});

describe('update', function () {
    it('updates a menu item', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $item = NavigationItem::factory()->visibleToAll()->create([
            'label' => 'Viejo',
            'route_name' => 'dashboard',
        ]);

        $this->actingAs($actor)
            ->patch(route('sistemas.navigation-items.update', $item), [
                'label' => 'Nuevo',
                'route_name' => 'profile.edit',
                'icon' => 'user',
                'sort_order' => 8,
                'is_active' => '1',
                'visible_to_all' => '1',
            ])
            ->assertRedirect(route('sistemas.navigation-items.index'))
            ->assertSessionHas('status', 'navigation-item-updated');

        expect($item->fresh()->label)->toBe('Nuevo')
            ->and($item->fresh()->route_name)->toBe('profile.edit');
    });
});

describe('destroy', function () {
    it('deletes a menu item', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $item = NavigationItem::factory()->create();

        $this->actingAs($actor)
            ->delete(route('sistemas.navigation-items.destroy', $item))
            ->assertRedirect(route('sistemas.navigation-items.index'))
            ->assertSessionHas('status', 'navigation-item-deleted');

        $this->assertModelMissing($item);
    });

    it('forbids administrators from deleting menu items', function () {
        $actor = assignRole(User::factory()->create(), Role::Admin);
        $item = NavigationItem::factory()->create();

        $this->actingAs($actor)
            ->delete(route('sistemas.navigation-items.destroy', $item))
            ->assertForbidden();

        $this->assertModelExists($item);
    });
});

describe('edit', function () {
    it('shows the selected menu item in the edit form', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $item = NavigationItem::factory()->create([
            'label' => 'Calificaciones',
        ]);

        $this->actingAs($actor)
            ->get(route('sistemas.navigation-items.edit', $item))
            ->assertSee('Calificaciones')
            ->assertSee('Editar opción de menú');
    });
});
