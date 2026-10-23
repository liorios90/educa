<?php

use App\Auth\ActiveRole;
use App\Enums\ModoDecimal;
use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

describe('edit', function () {
    it('redirects guests from the decimal settings to the login page', function () {
        $this->get(route('Admin.decimales'))
            ->assertRedirect(route('login'));
    });

    it('forbids other roles from opening the decimal settings', function (User $user) {
        $this->actingAs($user)
            ->get(route('Admin.decimales'))
            ->assertForbidden();
    })->with([
        'teacher' => fn () => assignRole(User::factory()->create([
            'establecimiento_id' => Establecimiento::factory()->create()->id,
        ]), Role::Docente),
        'secretary' => fn () => secretariaOf(Establecimiento::factory()->create()),
    ]);

    it('forbids an administrator without a school from opening the decimal settings', function () {
        $admin = assignRole(User::factory()->create(), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.decimales'))
            ->assertForbidden();
    });

    it('lets an administrator open the decimal settings for their school', function () {
        $establecimiento = Establecimiento::factory()->create([
            'nombre' => 'Unidad Educativa Andina',
            'decimales_calificacion' => 2,
            'modo_decimal' => ModoDecimal::Aproximar,
        ]);
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->get(route('Admin.decimales'))
            ->assertOk()
            ->assertSee('Decimales')
            ->assertSee('Unidad Educativa Andina')
            ->assertSee('Cantidad de decimales')
            ->assertSee('Aproximar al inmediato superior')
            ->assertSee('Truncar')
            ->assertSee('5.26')
            ->assertSee('5.25');
    });

    it('escapes a dangerous school name on the decimal settings page', function () {
        $establecimiento = Establecimiento::factory()->create([
            'nombre' => "<script>alert('xss')</script>",
        ]);
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->get(route('Admin.decimales'))
            ->assertDontSee("<script>alert('xss')</script>", false);
    });

    it('shows the decimal settings link to administrators', function () {
        $this->seed(NavigationSeeder::class);
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->withSession([ActiveRole::SESSION_KEY => Role::Admin->value])
            ->get(route('dashboard'))
            ->assertSee('Decimales')
            ->assertSee(route('Admin.decimales'), false);
    });
});

describe('update', function () {
    it('redirects guests from updating decimal settings to the login page', function () {
        $this->put(route('Admin.decimales.update'))
            ->assertRedirect(route('login'));
    });

    it('lets an administrator store decimal settings for their school', function () {
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'Unidad Educativa Andina']);
        $otro = Establecimiento::factory()->create([
            'decimales_calificacion' => 2,
            'modo_decimal' => ModoDecimal::Aproximar,
        ]);
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->from(route('Admin.decimales'))
            ->put(route('Admin.decimales.update'), [
                'decimales_calificacion' => 3,
                'modo_decimal' => ModoDecimal::Truncar->value,
                'nombre' => 'Instituto Hackeado',
            ])
            ->assertRedirect(route('Admin.decimales'))
            ->assertSessionHas('status', 'decimales-updated');

        $establecimiento->refresh();

        expect($establecimiento->decimales_calificacion)->toBe(3)
            ->and($establecimiento->modo_decimal)->toBe(ModoDecimal::Truncar)
            ->and($establecimiento->nombre)->toBe('Unidad Educativa Andina');

        $otro->refresh();

        expect($otro->decimales_calificacion)->toBe(2)
            ->and($otro->modo_decimal)->toBe(ModoDecimal::Aproximar);
    });

    it('asks for the decimal count and mode when they are missing', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->from(route('Admin.decimales'))
            ->put(route('Admin.decimales.update'), [])
            ->assertRedirect(route('Admin.decimales'))
            ->assertSessionHasErrors([
                'decimales_calificacion' => 'Indica con cuántos decimales trabaja el instituto.',
                'modo_decimal' => 'Indica si se trunca o se aproxima.',
            ]);
    });

    it('rejects a decimal count outside the allowed range', function (int $cantidad, string $mensaje) {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->from(route('Admin.decimales'))
            ->put(route('Admin.decimales.update'), [
                'decimales_calificacion' => $cantidad,
                'modo_decimal' => ModoDecimal::Aproximar->value,
            ])
            ->assertRedirect(route('Admin.decimales'))
            ->assertSessionHasErrors([
                'decimales_calificacion' => $mensaje,
            ]);
    })->with([
        'too high' => [5, 'La cantidad de decimales no puede ser mayor que 4.'],
        'negative' => [-1, 'La cantidad de decimales no puede ser menor que cero.'],
    ]);

    it('rejects an unknown decimal mode', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->from(route('Admin.decimales'))
            ->put(route('Admin.decimales.update'), [
                'decimales_calificacion' => 2,
                'modo_decimal' => 'redondear',
            ])
            ->assertRedirect(route('Admin.decimales'))
            ->assertSessionHasErrors([
                'modo_decimal' => 'Selecciona si se trunca o se aproxima al inmediato superior.',
            ]);
    });
});
