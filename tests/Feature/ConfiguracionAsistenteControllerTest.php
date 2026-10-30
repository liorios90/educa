<?php

use App\Auth\ActiveRole;
use App\Enums\AsistenteProveedor;
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
            ->assertSee('Proveedor')
            ->assertSee('API compatible con OpenAI')
            ->assertSee('Clave')
            ->assertSee('URL base')
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
                'asistente_proveedor' => 'gemini',
                'asistente_base_url' => 'http://127.0.0.1:11434/v1',
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
            ->and($establecimiento->proveedorAsistente())->toBe(AsistenteProveedor::Gemini)
            ->and($establecimiento->asistente_base_url)->toBeNull()
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
                'asistente_proveedor' => 'gemini',
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
                'asistente_proveedor' => 'gemini',
                'gemini_api_key' => '',
                'gemini_model' => '',
                'gemini_preguntas_limite' => 100,
                'gemini_tokens_limite' => 100000,
            ])
            ->assertRedirect(route('Admin.asistente'))
            ->assertSessionHasErrors([
                'gemini_api_key' => 'Ingresa la clave del asistente de este instituto.',
                'gemini_model' => 'Indica el modelo del asistente de este instituto.',
            ]);
    });

    it('rejects a model that could change the Gemini path', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->from(route('Admin.asistente'))
            ->put(route('Admin.asistente.update'), [
                'asistente_proveedor' => 'gemini',
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

    it('stores a compatible model only for the administrator school', function () {
        $establecimiento = Establecimiento::factory()->create();
        $otro = Establecimiento::factory()->create([
            'gemini_api_key' => 'clave-ajena',
            'gemini_model' => 'modelo-ajeno',
        ]);
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->from(route('Admin.asistente'))
            ->put(route('Admin.asistente.update'), [
                'asistente_proveedor' => 'compatible',
                'asistente_base_url' => 'https://api.groq.com/openai/v1',
                'gemini_api_key' => 'clave-andina',
                'gemini_model' => 'meta-llama/llama-3.1-8b-instant',
                'gemini_preguntas_limite' => 80,
                'gemini_tokens_limite' => 5000,
            ])
            ->assertRedirect(route('Admin.asistente'))
            ->assertSessionHas('status', 'asistente-updated');

        $establecimiento->refresh();

        expect($establecimiento->proveedorAsistente())->toBe(AsistenteProveedor::Compatible)
            ->and($establecimiento->asistente_base_url)->toBe('https://api.groq.com/openai/v1')
            ->and($establecimiento->gemini_model)->toBe('meta-llama/llama-3.1-8b-instant')
            ->and($establecimiento->gemini_api_key)->toBe('clave-andina');

        $otro->refresh();

        expect($otro->gemini_api_key)->toBe('clave-ajena')
            ->and($otro->gemini_model)->toBe('modelo-ajeno')
            ->and($otro->proveedorAsistente())->toBe(AsistenteProveedor::Gemini);
    });

    it('stores a local compatible url', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->from(route('Admin.asistente'))
            ->put(route('Admin.asistente.update'), [
                'asistente_proveedor' => 'compatible',
                'asistente_base_url' => 'http://127.0.0.1:11434/v1',
                'gemini_api_key' => 'clave-local',
                'gemini_model' => 'llama3.2',
                'gemini_preguntas_limite' => 100,
                'gemini_tokens_limite' => 100000,
            ])
            ->assertRedirect(route('Admin.asistente'))
            ->assertSessionHas('status', 'asistente-updated');

        $establecimiento->refresh();

        expect($establecimiento->asistente_base_url)->toBe('http://127.0.0.1:11434/v1');
    });

    it('asks for the base url when the provider is compatible', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->from(route('Admin.asistente'))
            ->put(route('Admin.asistente.update'), [
                'asistente_proveedor' => 'compatible',
                'asistente_base_url' => '',
                'gemini_api_key' => 'clave-andina',
                'gemini_model' => 'llama-3.1-8b-instant',
                'gemini_preguntas_limite' => 100,
                'gemini_tokens_limite' => 100000,
            ])
            ->assertRedirect(route('Admin.asistente'))
            ->assertSessionHasErrors([
                'asistente_base_url' => 'Ingresa la URL base del asistente.',
            ]);
    });

    it('rejects a link-local or private assistant url', function (string $url, string $mensaje) {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->from(route('Admin.asistente'))
            ->put(route('Admin.asistente.update'), [
                'asistente_proveedor' => 'compatible',
                'asistente_base_url' => $url,
                'gemini_api_key' => 'clave-andina',
                'gemini_model' => 'llama-3.1-8b-instant',
                'gemini_preguntas_limite' => 100,
                'gemini_tokens_limite' => 100000,
            ])
            ->assertRedirect(route('Admin.asistente'))
            ->assertSessionHasErrors([
                'asistente_base_url' => $mensaje,
            ]);
    })->with([
        'link local' => ['http://169.254.169.254', 'La URL base debe usar https, salvo en localhost.'],
        'private network' => ['https://10.0.0.1/v1', 'La URL base no puede apuntar a una red privada.'],
    ]);

    it('rejects a slash in a Gemini model name', function () {
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->from(route('Admin.asistente'))
            ->put(route('Admin.asistente.update'), [
                'asistente_proveedor' => 'gemini',
                'gemini_api_key' => 'clave-andina',
                'gemini_model' => 'meta-llama/llama-3.1-8b-instant',
                'gemini_preguntas_limite' => 100,
                'gemini_tokens_limite' => 100000,
            ])
            ->assertRedirect(route('Admin.asistente'))
            ->assertSessionHasErrors([
                'gemini_model' => 'El modelo solo puede contener letras, números, puntos, guiones y guiones bajos.',
            ]);
    });
});
