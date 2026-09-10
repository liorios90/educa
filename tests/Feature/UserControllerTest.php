<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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
                'role' => Role::Admin->value,
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
                'role' => Role::Sistemas->value,
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
                'role' => Role::Sistemas->value,
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
                'role' => Role::Sistemas->value,
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
                'role' => Role::Sistemas->value,
            ])
            ->assertSessionHasNoErrors();

        expect($user->fresh()->email_verified_at)->toBeNull();
    });

    it('redirects guests from the update route to login', function () {
        $user = User::factory()->create(['name' => 'Original']);

        $this->patch(route('admin.users.update', $user), [
            'name' => 'Cambiado',
            'email' => $user->email,
            'role' => Role::Sistemas->value,
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
            ->assertSessionHasErrors(['name', 'email', 'role']);
    });

    it('forbids systems users from updating a user', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $user = User::factory()->create(['name' => 'Original']);

        $this->actingAs($actor)
            ->patch(route('admin.users.update', $user), [
                'name' => 'Cambiado',
                'email' => $user->email,
                'role' => Role::Sistemas->value,
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
