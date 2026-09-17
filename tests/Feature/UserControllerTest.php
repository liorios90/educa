<?php

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as RoleModel;

describe('store', function () {
    it('allows a systems user to create a user', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->post(route('sistemas.users.store'), [
                'name' => 'Lourdes Flores',
                'email' => 'lourdes@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'roles' => [Role::Sistemas->value],
            ])
            ->assertRedirect(route('sistemas.users'))
            ->assertSessionHas('status', 'user-created');

        $created = User::query()->where('email', 'lourdes@example.com')->first();

        expect($created)->not->toBeNull()
            ->and($created->name)->toBe('Lourdes Flores')
            ->and($created->hasRole(Role::Sistemas))->toBeTrue();

        expect(Hash::check('password', $created->password))->toBeTrue();
    });

    it('allows a systems user to assign multiple roles', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        RoleModel::findOrCreate(Role::Admin->value, 'web');
        $establecimiento = Establecimiento::factory()->create();

        $this->actingAs($actor)
            ->post(route('sistemas.users.store'), [
                'name' => 'Lourdes Flores',
                'email' => 'lourdes@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'roles' => [Role::Sistemas->value, Role::Admin->value],
                'establecimiento_id' => $establecimiento->id,
            ])
            ->assertRedirect(route('sistemas.users'))
            ->assertSessionHasNoErrors();

        $created = User::query()->where('email', 'lourdes@example.com')->firstOrFail();

        expect($created->hasRole(Role::Sistemas))->toBeTrue()
            ->and($created->hasRole(Role::Admin))->toBeTrue()
            ->and($created->establecimiento_id)->toBe($establecimiento->id);
    });

    it('shows role checkboxes on the create form', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->get(route('sistemas.users.create'))
            ->assertOk()
            ->assertSee('name="roles[]"', false)
            ->assertSee('Administrador')
            ->assertSee('Sistemas')
            ->assertSee('Secretaría');
    });

    it('requires an establishment when a systems user creates a school-bound role', function (Role $role) {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->from(route('sistemas.users.create'))
            ->post(route('sistemas.users.store'), [
                'name' => 'Director Andino',
                'email' => 'director@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'roles' => [$role->value],
            ])
            ->assertRedirect(route('sistemas.users.create'))
            ->assertSessionHasErrors([
                'establecimiento_id' => 'Este rol debe pertenecer a un establecimiento.',
            ]);

        expect(User::query()->where('email', 'director@example.com')->exists())->toBeFalse();
    })->with([
        'administrator' => Role::Admin,
        'secretaria' => Role::Secretaria,
    ]);

    it('assigns the establishment when a systems user creates a school-bound role', function (Role $role) {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        RoleModel::findOrCreate($role->value, 'web');
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'UE Los Andes']);

        $this->actingAs($actor)
            ->post(route('sistemas.users.store'), [
                'name' => 'Director Andino',
                'email' => 'director@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'roles' => [$role->value],
                'establecimiento_id' => $establecimiento->id,
            ])
            ->assertRedirect(route('sistemas.users'))
            ->assertSessionHas('status', 'user-created');

        $created = User::query()->where('email', 'director@example.com')->firstOrFail();

        expect($created->hasRole($role))->toBeTrue()
            ->and($created->establecimiento_id)->toBe($establecimiento->id);
    })->with([
        'administrator' => Role::Admin,
        'secretaria' => Role::Secretaria,
    ]);

    it('does not keep an establishment when the role is sistemas', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();

        $this->actingAs($actor)
            ->post(route('sistemas.users.store'), [
                'name' => 'Lourdes Flores',
                'email' => 'lourdes@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'roles' => [Role::Sistemas->value],
                'establecimiento_id' => $establecimiento->id,
            ])
            ->assertRedirect(route('sistemas.users'));

        expect(User::query()->where('email', 'lourdes@example.com')->value('establecimiento_id'))->toBeNull();
    });

    it('lists establishments on the create form', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        Establecimiento::factory()->create(['nombre' => 'UE Cotopaxi']);

        $this->actingAs($actor)
            ->get(route('sistemas.users.create'))
            ->assertSee('Establecimiento')
            ->assertSee('UE Cotopaxi');
    });

    it('allows an administrator to create a user', function () {
        $admin = assignRole(User::factory()->create(), Role::Admin);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Lourdes Flores',
                'email' => 'lourdes@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'roles' => [Role::Admin->value],
            ])
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('status', 'user-created');

        $created = User::query()->where('email', 'lourdes@example.com')->first();

        expect($created)->not->toBeNull()
            ->and($created->name)->toBe('Lourdes Flores')
            ->and($created->hasRole(Role::Admin))->toBeTrue();
    });

    it('forbids a secretary from creating a user', function () {
        $actor = assignRole(User::factory()->create(), Role::Secretaria);

        $this->actingAs($actor)
            ->post(route('sistemas.users.store'), [
                'name' => 'Lourdes Flores',
                'email' => 'lourdes@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'roles' => [Role::Secretaria->value],
            ])
            ->assertForbidden();

        expect(User::query()->where('email', 'lourdes@example.com')->exists())->toBeFalse();
    });

    it('redirects guests from the store route to login', function () {
        $this->post(route('sistemas.users.store'), [
            'name' => 'Lourdes Flores',
            'email' => 'lourdes@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [Role::Sistemas->value],
        ])
            ->assertRedirect(route('login'));

        expect(User::query()->where('email', 'lourdes@example.com')->exists())->toBeFalse();
    });

    it('rejects an empty create payload', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->from(route('sistemas.users.create'))
            ->post(route('sistemas.users.store'), [])
            ->assertRedirect(route('sistemas.users.create'))
            ->assertSessionHasErrors(['name', 'email', 'password', 'roles']);
    });
});

describe('update', function () {
    it('allows an administrator to update another user', function () {
        $admin = assignRole(User::factory()->create(), Role::Admin);
        $user = assignRole(User::factory()->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
        ]), Role::Sistemas);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => 'Ana Gómez',
                'email' => 'ana.gomez@example.com',
                'roles' => [Role::Admin->value],
            ])
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('status', 'user-updated');

        $user->refresh();

        expect($user->name)->toBe('Ana Gómez')
            ->and($user->email)->toBe('ana.gomez@example.com')
            ->and($user->hasRole(Role::Admin))->toBeTrue()
            ->and($user->hasRole(Role::Sistemas))->toBeFalse();
    });

    it('keeps the password when it is left blank', function () {
        $admin = assignRole(User::factory()->create(), Role::Admin);
        $user = assignRole(User::factory()->create([
            'password' => 'secret-password',
        ]), Role::Sistemas);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => [Role::Sistemas->value],
            ])
            ->assertSessionHasNoErrors();

        expect(Hash::check('secret-password', $user->fresh()->password))->toBeTrue();
    });

    it('updates the password when a new one is provided', function () {
        $admin = assignRole(User::factory()->create(), Role::Admin);
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
                'roles' => [Role::Sistemas->value],
            ])
            ->assertSessionHasNoErrors();

        expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
    });

    it('rejects an email that already belongs to another user', function () {
        $admin = assignRole(User::factory()->create(), Role::Admin);
        User::factory()->create(['email' => 'taken@example.com']);
        $user = assignRole(User::factory()->create(['email' => 'libre@example.com']), Role::Sistemas);

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $user))
            ->patch(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => 'taken@example.com',
                'roles' => [Role::Sistemas->value],
            ])
            ->assertRedirect(route('admin.users.edit', $user))
            ->assertSessionHasErrors('email');

        expect($user->fresh()->email)->toBe('libre@example.com');
    });

    it('clears email verification when the email changes', function () {
        $admin = assignRole(User::factory()->create(), Role::Admin);
        $user = assignRole(User::factory()->create([
            'email' => 'viejo@example.com',
            'email_verified_at' => now(),
        ]), Role::Sistemas);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => 'nuevo@example.com',
                'roles' => [Role::Sistemas->value],
            ])
            ->assertSessionHasNoErrors();

        expect($user->fresh()->email_verified_at)->toBeNull();
    });

    it('redirects guests from the update route to login', function () {
        $user = User::factory()->create(['name' => 'Original']);

        $this->patch(route('admin.users.update', $user), [
            'name' => 'Cambiado',
            'email' => $user->email,
            'roles' => [Role::Sistemas->value],
        ])
            ->assertRedirect(route('login'));

        expect($user->fresh()->name)->toBe('Original');
    });

    it('rejects an empty update payload', function () {
        $admin = assignRole(User::factory()->create(), Role::Admin);
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $user))
            ->patch(route('admin.users.update', $user), [])
            ->assertRedirect(route('admin.users.edit', $user))
            ->assertSessionHasErrors(['name', 'email', 'roles']);
    });

    it('allows a systems user to update another user', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $user = assignRole(User::factory()->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
        ]), Role::Secretaria);

        $this->actingAs($actor)
            ->patch(route('sistemas.users.update', $user), [
                'name' => 'Ana Gómez',
                'email' => 'ana.gomez@example.com',
                'roles' => [Role::Sistemas->value],
            ])
            ->assertRedirect(route('sistemas.users'))
            ->assertSessionHas('status', 'user-updated');

        $user->refresh();

        expect($user->name)->toBe('Ana Gómez')
            ->and($user->email)->toBe('ana.gomez@example.com')
            ->and($user->hasRole(Role::Sistemas))->toBeTrue()
            ->and($user->hasRole(Role::Secretaria))->toBeFalse();
    });

    it('requires an establishment when a systems user changes a user to a school-bound role', function (Role $role) {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->from(route('sistemas.users.edit', $user))
            ->patch(route('sistemas.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => [$role->value],
            ])
            ->assertRedirect(route('sistemas.users.edit', $user))
            ->assertSessionHasErrors([
                'establecimiento_id' => 'Este rol debe pertenecer a un establecimiento.',
            ]);

        expect($user->fresh()->hasRole($role))->toBeFalse();
    })->with([
        'administrator' => Role::Admin,
        'secretaria' => Role::Secretaria,
    ]);

    it('assigns the establishment when a systems user changes a user to a school-bound role', function (Role $role) {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        RoleModel::findOrCreate($role->value, 'web');
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'UE Los Andes']);

        $this->actingAs($actor)
            ->patch(route('sistemas.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => [$role->value],
                'establecimiento_id' => $establecimiento->id,
            ])
            ->assertRedirect(route('sistemas.users'))
            ->assertSessionHas('status', 'user-updated');

        $user->refresh();

        expect($user->hasRole($role))->toBeTrue()
            ->and($user->establecimiento_id)->toBe($establecimiento->id);
    })->with([
        'administrator' => Role::Admin,
        'secretaria' => Role::Secretaria,
    ]);

    it('clears the establishment when the role no longer belongs to a school', function (Role $role) {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        $user = assignRole(User::factory()->create([
            'establecimiento_id' => $establecimiento->id,
        ]), $role);

        $this->actingAs($actor)
            ->patch(route('sistemas.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => [Role::Sistemas->value],
                'establecimiento_id' => $establecimiento->id,
            ])
            ->assertRedirect(route('sistemas.users'));

        $user->refresh();

        expect($user->hasRole(Role::Sistemas))->toBeTrue()
            ->and($user->establecimiento_id)->toBeNull();
    })->with([
        'administrator' => Role::Admin,
        'secretaria' => Role::Secretaria,
    ]);

    it('forbids systems users from updating a user', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $user = User::factory()->create(['name' => 'Original']);

        $this->actingAs($actor)
            ->patch(route('admin.users.update', $user), [
                'name' => 'Cambiado',
                'email' => $user->email,
                'roles' => [Role::Sistemas->value],
            ])
            ->assertForbidden();

        expect($user->fresh()->name)->toBe('Original');
    });
});

describe('edit', function () {
    it('shows the selected user in the edit form', function () {
        $admin = assignRole(User::factory()->create(), Role::Admin);
        $user = assignRole(User::factory()->create([
            'name' => 'Carlos Ruiz',
            'email' => 'carlos@example.com',
        ]), Role::Sistemas);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertSee('Carlos Ruiz')
            ->assertSee('carlos@example.com')
            ->assertSee('Editar usuario');
    });

    it('forbids systems users from opening the edit form', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $user = User::factory()->create();

        $this->actingAs($actor)
            ->get(route('admin.users.edit', $user))
            ->assertForbidden();
    });

    it('redirects guests from the edit form to login', function () {
        $user = User::factory()->create();

        $this->get(route('admin.users.edit', $user))
            ->assertRedirect(route('login'));
    });
});

describe('destroy', function () {
    it('allows an administrator to delete another user', function () {
        $admin = assignRole(User::factory()->create(), Role::Admin);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('status', 'user-deleted');

        $this->assertModelMissing($user);
    });

    it('does not allow an administrator to delete their own account', function () {
        $admin = assignRole(User::factory()->create(), Role::Admin);

        $this->actingAs($admin)
            ->from(route('admin.users'))
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('status', 'user-self-delete-blocked');

        $this->assertModelExists($admin);
    });

    it('allows a systems user to delete another user', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $user = User::factory()->create();

        $this->actingAs($actor)
            ->delete(route('sistemas.users.destroy', $user))
            ->assertRedirect(route('sistemas.users'))
            ->assertSessionHas('status', 'user-deleted');

        $this->assertModelMissing($user);
    });

    it('forbids systems users from deleting a user', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $user = User::factory()->create();

        $this->actingAs($actor)
            ->delete(route('admin.users.destroy', $user))
            ->assertForbidden();

        $this->assertModelExists($user);
    });

    it('redirects guests from the destroy route to login', function () {
        $user = User::factory()->create();

        $this->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('login'));

        $this->assertModelExists($user);
    });
});

describe('index', function () {
    it('shows edit and delete actions for other users', function () {
        $admin = assignRole(User::factory()->create(), Role::Admin);
        $user = User::factory()->create(['name' => 'Luisa Mora']);

        $this->actingAs($admin)
            ->get(route('admin.users'))
            ->assertSee('Luisa Mora')
            ->assertSee(route('admin.users.edit', $user), false)
            ->assertSee(route('admin.users.destroy', $user), false);
    });

    it('shows the establishment of an administrator in the users list', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'UE Cotopaxi']);
        assignRole(User::factory()->create([
            'name' => 'Director Andino',
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Admin);

        $this->actingAs($actor)
            ->get(route('sistemas.users'))
            ->assertSee('Director Andino')
            ->assertSee('UE Cotopaxi');
    });

    it('shows edit and delete actions for systems users', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $user = User::factory()->create(['name' => 'Luisa Mora']);

        $this->actingAs($actor)
            ->get(route('sistemas.users'))
            ->assertSee('Luisa Mora')
            ->assertSee(route('sistemas.users.edit', $user), false)
            ->assertSee(route('sistemas.users.destroy', $user), false);
    });

    it('escapes user names in the list', function () {
        $admin = assignRole(User::factory()->create(), Role::Admin);
        User::factory()->create([
            'name' => "<script>alert('xss')</script>",
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users'))
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });
});
