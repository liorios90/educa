<?php

use App\Enums\Role;
use App\Models\User;
use App\Navigation\Navigation;
use Database\Seeders\NavigationSeeder;

it('shows shared links and hides role links for users without a role', function () {
    $this->seed(NavigationSeeder::class);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('Inicio')
        ->assertSee('Perfil')
        ->assertDontSee('Usuarios')
        ->assertDontSee(route('admin.users'), false)
        ->assertDontSee(route('sistemas.home'), false);
});

it('shows the users link only to administrators', function () {
    $this->seed(NavigationSeeder::class);

    $user = assignRole(User::factory()->create(), Role::Admin);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee(route('admin.users'), false)
        ->assertDontSee(route('sistemas.home'), false);
});

it('shows the systems link only to systems users', function () {
    $this->seed(NavigationSeeder::class);

    $user = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee(route('sistemas.home'), false)
        ->assertDontSee(route('admin.users'), false);
});

it('redirects a multi-role user to choose a role before the dashboard', function () {
    $this->seed(NavigationSeeder::class);

    $user = User::factory()->create();
    assignRole($user, Role::Admin);
    assignRole($user, Role::Sistemas);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('role.select'));
});

it('allows administrators to open the users page', function () {
    $user = assignRole(User::factory()->create(), Role::Admin);

    $this->actingAs($user)
        ->get(route('admin.users'))
        ->assertSee('Nuevo usuario');
});

it('forbids systems users from opening the users page', function () {
    $user = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($user)
        ->get(route('admin.users'))
        ->assertForbidden();
});

it('allows systems users to open the systems page', function () {
    $user = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($user)
        ->get(route('sistemas.home'))
        ->assertSee('Herramientas técnicas reservadas al rol Sistemas');
});

it('forbids administrators from opening the systems page', function () {
    $user = assignRole(User::factory()->create(), Role::Admin);

    $this->actingAs($user)
        ->get(route('sistemas.home'))
        ->assertForbidden();
});

it('redirects guests from role pages to login', function (string $routeName) {
    $this->get(route($routeName))
        ->assertRedirect(route('login'));
})->with([
    'users page' => 'admin.users',
    'systems page' => 'sistemas.home',
]);

it('escapes the user name in the sidebar', function () {
    $user = User::factory()->create([
        'name' => "<script>alert('xss')</script>",
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee("<script>alert('xss')</script>")
        ->assertDontSee("<script>alert('xss')</script>", false);
});

it('returns only the links the user may see', function () {
    $this->seed(NavigationSeeder::class);

    $admin = assignRole(User::factory()->create(), Role::Admin);

    $labels = collect((new Navigation)->itemsFor($admin))
        ->map(fn ($item) => $item->label)
        ->all();

    expect($labels)->toBe(['Inicio', 'Padres', 'Alumnos', 'Empleados', 'Reportes', 'Perfil']);
});
