<?php

use App\Auth\ActiveRole;
use App\Enums\Role;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

it('sends a multi-role user to choose a role after login', function () {
    $user = User::factory()->create();
    assignRole($user, Role::Admin);
    assignRole($user, Role::Sistemas);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('role.select'));

    $this->assertAuthenticated();
    expect(session(ActiveRole::SESSION_KEY))->toBeNull();
});

it('lets a multi-role user enter with the selected role', function () {
    $this->seed(NavigationSeeder::class);
    $user = User::factory()->create();
    assignRole($user, Role::Admin);
    assignRole($user, Role::Sistemas);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('role.select'));

    $this->actingAs($user)
        ->get(route('role.select'))
        ->assertOk()
        ->assertSee('¿Con qué rol quieres entrar?')
        ->assertSee('Administrador')
        ->assertSee('Sistemas');

    $this->actingAs($user)
        ->post(route('role.store'), ['role' => Role::Sistemas->value])
        ->assertRedirect(route('dashboard', absolute: false));

    expect(session(ActiveRole::SESSION_KEY))->toBe(Role::Sistemas->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('sistemas.home'), false)
        ->assertDontSee('Usuarios');
});

it('forbids a multi-role user from opening another role area after choosing', function () {
    $user = User::factory()->create();
    assignRole($user, Role::Admin);
    assignRole($user, Role::Sistemas);

    $this->actingAs($user)
        ->post(route('role.store'), ['role' => Role::Admin->value])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->actingAs($user)
        ->get(route('sistemas.home'))
        ->assertForbidden();
});

it('rejects a role the user does not have', function () {
    $user = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($user)
        ->from(route('role.select'))
        ->post(route('role.store'), ['role' => Role::Admin->value])
        ->assertRedirect(route('role.select'))
        ->assertSessionHasErrors('role');
});

it('returns a multi-role user to the intended page after choosing a role', function () {
    $user = User::factory()->create();
    assignRole($user, Role::Admin);
    assignRole($user, Role::Sistemas);

    $this->actingAs($user)
        ->get(route('sistemas.home'))
        ->assertRedirect(route('role.select'));

    $this->actingAs($user)
        ->post(route('role.store'), ['role' => Role::Sistemas->value])
        ->assertRedirect(route('sistemas.home'));
});

it('does not ask for a role when the user has only one', function () {
    $user = assignRole(User::factory()->create(), Role::Sistemas);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('dashboard', absolute: false));

    expect(session(ActiveRole::SESSION_KEY))->toBe(Role::Sistemas->value);
});
