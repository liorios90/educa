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

        foreach (range(1, 11) as $number) {
            NavigationItem::factory()->create([
                'sort_order' => $number,
                'label' => 'Opcion '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            ]);
        }

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index'))
            ->assertSee('Opcion 01')
            ->assertSee('Opcion 10')
            ->assertDontSee('Opcion 11')
            ->assertSee('Mostrando 1–10 de 11')
            ->assertSee('page=2', false);

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', ['page' => 2]))
            ->assertSee('Opcion 11')
            ->assertDontSee('Opcion 01')
            ->assertSee('Mostrando 11–11 de 11');
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

    it('sorts accented labels with the unaccented alphabet', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        NavigationItem::factory()->create(['label' => 'Zorro', 'sort_order' => 1]);
        NavigationItem::factory()->create(['label' => 'Áreas', 'sort_order' => 2]);
        NavigationItem::factory()->create(['label' => 'Abeja', 'sort_order' => 3]);

        $html = $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', [
                'sort' => 'label',
                'direction' => 'asc',
            ]))
            ->assertOk()
            ->getContent();

        expect(strpos($html, 'Abeja'))->toBeLessThan(strpos($html, 'Áreas'))
            ->and(strpos($html, 'Áreas'))->toBeLessThan(strpos($html, 'Zorro'));
    });

    it('sorts the route column by the displayed name', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        NavigationItem::factory()->create([
            'label' => 'Menu Profile',
            'route_name' => 'profile.edit',
            'sort_order' => 1,
            'is_active' => false,
        ]);
        NavigationItem::factory()->group()->create([
            'label' => 'Menu Catalogos',
            'sort_order' => 2,
            'is_active' => false,
        ]);
        NavigationItem::factory()->create([
            'label' => 'Menu Dashboard',
            'route_name' => 'dashboard',
            'sort_order' => 3,
            'is_active' => false,
        ]);

        $html = $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', [
                'sort' => 'route_name',
                'direction' => 'asc',
            ]))
            ->assertOk()
            ->assertSee('Botones')
            ->getContent();

        expect(strpos($html, 'Menu Catalogos'))->toBeLessThan(strpos($html, 'Menu Dashboard'))
            ->and(strpos($html, 'Menu Dashboard'))->toBeLessThan(strpos($html, 'Menu Profile'));
    });

    it('sorts the visibility column by the displayed name', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $adminRole = RoleModel::findOrCreate(Role::Admin->value, 'web');
        $sistemasRole = RoleModel::findOrCreate(Role::Sistemas->value, 'web');

        NavigationItem::factory()->visibleToAll()->inactive()->create([
            'label' => 'Vis Todos',
            'sort_order' => 1,
        ]);
        $sistemas = NavigationItem::factory()->inactive()->create([
            'label' => 'Vis Sistemas',
            'sort_order' => 2,
        ]);
        $sistemas->roles()->sync([$sistemasRole->id]);
        NavigationItem::factory()->inactive()->create([
            'label' => 'Vis Vacio',
            'sort_order' => 3,
        ]);
        $admin = NavigationItem::factory()->inactive()->create([
            'label' => 'Vis Admin',
            'sort_order' => 4,
        ]);
        $admin->roles()->sync([$adminRole->id]);

        $html = $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', [
                'sort' => 'visible_to_all',
                'direction' => 'asc',
            ]))
            ->assertOk()
            ->assertSee('Administrador')
            ->assertSee('Sin roles')
            ->assertSee('Todos')
            ->getContent();

        expect(strpos($html, 'Vis Admin'))->toBeLessThan(strpos($html, 'Vis Vacio'))
            ->and(strpos($html, 'Vis Vacio'))->toBeLessThan(strpos($html, 'Vis Sistemas'))
            ->and(strpos($html, 'Vis Sistemas'))->toBeLessThan(strpos($html, 'Vis Todos'));
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

    it('finds menu options without matching accents', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        NavigationItem::factory()->create(['label' => 'Áreas y asignaturas']);
        NavigationItem::factory()->create(['label' => 'Ayuda']);

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', ['q' => 'areas']))
            ->assertOk()
            ->assertSee('Áreas y asignaturas')
            ->assertDontSee('Ayuda');
    });

    it('finds a group when the search matches a submenu', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $group = NavigationItem::factory()->group()->create(['label' => 'Catálogos']);
        NavigationItem::factory()->childOf($group)->create(['label' => 'Jornadas']);
        NavigationItem::factory()->create(['label' => 'Ayuda']);

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', ['q' => 'jornadas']))
            ->assertOk()
            ->assertSee('Catálogos')
            ->assertDontSee('Ayuda');
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

    it('keeps the search term on the next page', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        foreach (range(1, 11) as $number) {
            NavigationItem::factory()->create([
                'sort_order' => $number,
                'label' => 'Reporte '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            ]);
        }

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', ['q' => 'Reporte', 'page' => 2]))
            ->assertSee('Reporte 11')
            ->assertDontSee('Reporte 01')
            ->assertSee('q=Reporte', false);
    });

    it('does not copy unknown query parameters into sort links', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        NavigationItem::factory()->create(['label' => 'Reportes']);

        $this->actingAs($user)
            ->get(route('sistemas.navigation-items.index', ['XDEBUG_SESSION' => '1']))
            ->assertOk()
            ->assertSee('sort=label', false)
            ->assertDontSee('XDEBUG_SESSION', false);
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
