<?php

use App\Auth\AuthContext;
use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\User;

it('stores the user in the session after login', function () {
    $user = User::factory()->create([
        'name' => 'Lourdes Flores',
        'email' => 'lourdes@example.com',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect();

    expect(session(AuthContext::USER_KEY))->toMatchArray([
        'id' => $user->id,
        'name' => 'Lourdes Flores',
        'email' => 'lourdes@example.com',
        'establecimiento_id' => null,
        'roles' => [],
    ])
        ->and(session(AuthContext::USER_KEY))->not->toHaveKey('password')
        ->and(session(AuthContext::ESTABLECIMIENTO_KEY))->toBeNull();
});

it('stores the establishment in the session when the user belongs to one', function () {
    $establecimiento = Establecimiento::factory()->create([
        'nombre' => 'UE Los Andes',
        'codigo_amie' => '17H00001',
        'regimen' => 'Sierra',
        'logo' => 'establecimientos/logos/ue-andes.png',
    ]);
    $user = assignRole(User::factory()->create([
        'name' => 'Director Andino',
        'email' => 'director@example.com',
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Admin);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect();

    expect(session(AuthContext::USER_KEY))->toMatchArray([
        'id' => $user->id,
        'name' => 'Director Andino',
        'email' => 'director@example.com',
        'establecimiento_id' => $establecimiento->id,
        'roles' => [Role::Admin->value],
    ])
        ->and(session(AuthContext::ESTABLECIMIENTO_KEY))->toMatchArray([
            'id' => $establecimiento->id,
            'nombre' => 'UE Los Andes',
            'codigo_amie' => '17H00001',
            'regimen' => 'Sierra',
            'logo' => 'establecimientos/logos/ue-andes.png',
        ]);
});

it('does not store credentials in the session after a failed login', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    expect(session(AuthContext::USER_KEY))->toBeNull()
        ->and(session(AuthContext::ESTABLECIMIENTO_KEY))->toBeNull();
});

it('stores user and establishment in the session before a multi-role user chooses a role', function () {
    $establecimiento = Establecimiento::factory()->create([
        'nombre' => 'UE Multi Rol',
    ]);
    $user = User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
    ]);
    assignRole($user, Role::Admin);
    assignRole($user, Role::Sistemas);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('role.select'));

    expect(session(AuthContext::USER_KEY))->toMatchArray([
        'id' => $user->id,
        'email' => $user->email,
        'establecimiento_id' => $establecimiento->id,
    ])
        ->and(session(AuthContext::USER_KEY)['roles'])->toEqualCanonicalizing([
            Role::Admin->value,
            Role::Sistemas->value,
        ])
        ->and(session(AuthContext::ESTABLECIMIENTO_KEY))->toMatchArray([
            'id' => $establecimiento->id,
            'nombre' => 'UE Multi Rol',
        ]);
});

it('hydrates the session context when it is missing on an authenticated request', function () {
    $user = assignRole(User::factory()->create([
        'name' => 'Ana Sistemas',
        'email' => 'ana@example.com',
    ]), Role::Sistemas);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    expect(session(AuthContext::USER_KEY))->toMatchArray([
        'id' => $user->id,
        'name' => 'Ana Sistemas',
        'email' => 'ana@example.com',
        'establecimiento_id' => null,
        'roles' => [Role::Sistemas->value],
    ])
        ->and(session(AuthContext::ESTABLECIMIENTO_KEY))->toBeNull();
});
