<?php

use App\Auth\ActiveRole;
use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

describe('edit', function () {
    it('redirects guests from the assistant settings to the login page', function () {
        $this->get(route('Admin.asistente'))
            ->assertRedirect(route('login'));
    });

    it('forbids other roles from opening the assistant settings', function (User $user) {
        $this->actingAs($user)
            ->get(route('Admin.asistente'))
            ->assertForbidden();
    })->with([
        'teacher' => fn () => assignRole(User::factory()->create([
            'establecimiento_id' => Establecimiento::factory()->create()->id,
        ]), Role::Docente),
        'secretary' => fn () => secretariaOf(Establecimiento::factory()->create()),
    ]);

    it('forbids an administrator without a school from opening the assistant settings', function () {
        $admin = assignRole(User::factory()->create(), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.asistente'))
            ->assertForbidden();
    });

    it('lets an administrator open the assistant settings for their school', function () {
        $establecimiento = Establecimiento::factory()->create([
            'nombre' => 'Unidad Educativa Andina',
            'gemini_api_key' => 'clave-secreta-andina',
            'gemini_model' => 'gemini-3.6-flash',
        ]);
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->get(route('Admin.asistente'))
            ->assertOk()
            ->assertSee('Asistente')
            ->assertSee('Unidad Educativa Andina')
            ->assertSee('Clave de Gemini')
            ->assertSee('Hay una clave guardada. Déjala en blanco para conservarla.')
            ->assertSee('gemini-3.6-flash')
            ->assertSee('Preguntas por mes')
            ->assertSee('Tokens por mes')
            ->assertDontSee('clave-secreta-andina', false);
    });

    it('escapes a dangerous school name on the assistant settings page', function () {
        $establecimiento = Establecimiento::factory()->create([
            'nombre' => "<script>alert('xss')</script>",
        ]);
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->get(route('Admin.asistente'))
            ->assertDontSee("<script>alert('xss')</script>", false);
    });

    it('shows the assistant settings link to administrators', function () {
        $this->seed(NavigationSeeder::class);
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->withSession([ActiveRole::SESSION_KEY => Role::Admin->value])
            ->get(route('dashboard'))
            ->assertSee('Asistente')
            ->assertSee(route('Admin.asistente'), false);
    });
});

describe('update', function () {
    it('redirects guests from updating assistant settings to the login page', function () {
        $this->put(route('Admin.asistente.update'))
            ->assertRedirect(route('login'));
    });

    it('lets an administrator store Gemini credentials for their school', function () {
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'Unidad Educativa Andina']);
        $otro = Establecimiento::factory()->create([
            'gemini_api_key' => 'clave-ajena',
            'gemini_model' => 'modelo-ajeno',
        ]);
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->from(route('Admin.asistente'))
            ->put(route('Admin.asistente.update'), [
                'gemini_api_key' => 'clave-andina',
                'gemini_model' => 'gemini-andina',
                'gemini_preguntas_limite' => 80,
                'gemini_tokens_limite' => 5000,
                'nombre' => 'Instituto Hackeado',
            ])
            ->assertRedirect(route('Admin.asistente'))
            ->assertSessionHas('status', 'asistente-updated');

        $establecimiento->refresh();

        expect($establecimiento->gemini_api_key)->toBe('clave-andina')
            ->and($establecimiento->gemini_model)->toBe('gemini-andina')
            ->and($establecimiento->gemini_preguntas_limite)->toBe(80)
            ->and($establecimiento->gemini_tokens_limite)->toBe(5000)
            ->and($establecimiento->nombre)->toBe('Unidad Educativa Andina')
            ->and($establecimiento->toArray())->not->toHaveKey('gemini_api_key');

        $otro->refresh();

        expect($otro->gemini_api_key)->toBe('clave-ajena')
            ->and($otro->gemini_model)->toBe('modelo-ajeno');
    });

    it('keeps the stored key when the administrator leaves it blank', function () {
        $establecimiento = Establecimiento::factory()->create([
            'gemini_api_key' => 'clave-actual',
            'gemini_model' => 'modelo-actual',
        ]);
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->from(route('Admin.asistente'))
            ->put(route('Admin.asistente.update'), [
                'gemini_api_key' => '',
                'gemini_model' => 'modelo-nuevo',
                'gemini_preguntas_limite' => 100,
                'gemini_tokens_limite' => 100000,
            ])
            ->assertRedirect(route('Admin.asistente'));

        $establecimiento->refresh();

        expect($establecimiento->gemini_api_key)->toBe('clave-actual')
            ->and($establecimiento->gemini_model)->toBe('modelo-nuevo');
    });

    it('asks for a key when the school has never configured Gemini', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->from(route('Admin.asistente'))
            ->put(route('Admin.asistente.update'), [
                'gemini_api_key' => '',
                'gemini_model' => '',
                'gemini_preguntas_limite' => 100,
                'gemini_tokens_limite' => 100000,
            ])
            ->assertRedirect(route('Admin.asistente'))
            ->assertSessionHasErrors([
                'gemini_api_key' => 'Ingresa la clave de Gemini de este instituto.',
                'gemini_model' => 'Indica el modelo de Gemini de este instituto.',
            ]);
    });

    it('rejects a model that could change the Gemini path', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->from(route('Admin.asistente'))
            ->put(route('Admin.asistente.update'), [
                'gemini_api_key' => 'clave-andina',
                'gemini_model' => '../otro-modelo',
                'gemini_preguntas_limite' => 100,
                'gemini_tokens_limite' => 100000,
            ])
            ->assertRedirect(route('Admin.asistente'))
            ->assertSessionHasErrors([
                'gemini_model' => 'El modelo solo puede contener letras, números, puntos, guiones y guiones bajos.',
            ]);
    });
});
