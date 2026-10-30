<?php

use App\Exceptions\GeminiCredentialsMissingException;
use App\Models\AsistenteConsulta;
use App\Models\Establecimiento;
use App\Models\User;
use App\Services\AsistenteCompatibleService;
use App\Services\AsistenteService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

it('sends the prompt to the compatible model and not to Gemini', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.groq.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Desde groq',
                    ],
                ],
            ],
            'usage' => [
                'prompt_tokens' => 3,
                'completion_tokens' => 4,
            ],
        ]),
    ]);

    $establecimiento = Establecimiento::factory()->create([
        'gemini_api_key' => 'clave-groq',
        'gemini_model' => 'meta-llama/llama-3.1-8b-instant',
        'asistente_proveedor' => 'compatible',
        'asistente_base_url' => 'https://api.groq.com/openai/v1',
    ]);
    $usuario = User::factory()->create(['name' => 'Ana Perez']);
    $this->actingAs($usuario);

    $respuesta = app(AsistenteService::class)->generate('Resume este tema', $establecimiento, 'Sistema');

    expect($respuesta->texto)->toBe('Desde groq')
        ->and($respuesta->tokens)->toBe(7);

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer clave-groq')
            && $request['model'] === 'meta-llama/llama-3.1-8b-instant'
            && $request['messages'][0] === ['role' => 'system', 'content' => 'Sistema']
            && $request['messages'][1] === ['role' => 'user', 'content' => 'Resume este tema'];
    });

    $this->assertDatabaseHas('asistente_consultas', [
        'establecimiento_id' => $establecimiento->id,
        'user_id' => $usuario->id,
        'pregunta' => 'Resume este tema',
    ]);
});

it('prefers the total token count from a compatible response', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.groq.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Con total']],
            ],
            'usage' => [
                'total_tokens' => 10,
                'prompt_tokens' => 1,
                'completion_tokens' => 1,
            ],
        ]),
    ]);

    $establecimiento = Establecimiento::factory()->create([
        'gemini_api_key' => 'clave-groq',
        'gemini_model' => 'llama',
        'asistente_proveedor' => 'compatible',
        'asistente_base_url' => 'https://api.groq.com/openai/v1/chat/completions',
    ]);

    $respuesta = app(AsistenteCompatibleService::class)->generate('Hola', $establecimiento);

    expect($respuesta->tokens)->toBe(10);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.groq.com/openai/v1/chat/completions');
});

it('does not follow a redirect from a compatible model', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.groq.com/*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest']),
    ]);

    $establecimiento = Establecimiento::factory()->create([
        'gemini_api_key' => 'clave-groq',
        'gemini_model' => 'llama',
        'asistente_proveedor' => 'compatible',
        'asistente_base_url' => 'https://api.groq.com/openai/v1',
    ]);

    expect(fn () => app(AsistenteCompatibleService::class)->generate('Hola', $establecimiento))
        ->toThrow(RequestException::class);

    Http::assertSentCount(1);
});

it('does not call a compatible model when the base url is missing', function () {
    Http::preventStrayRequests();

    $establecimiento = Establecimiento::factory()->create([
        'gemini_api_key' => 'clave-groq',
        'gemini_model' => 'llama',
        'asistente_proveedor' => 'compatible',
        'asistente_base_url' => null,
    ]);

    expect(fn () => app(AsistenteService::class)->generate('Hola', $establecimiento))
        ->toThrow(GeminiCredentialsMissingException::class);

    Http::assertNothingSent();
    expect(AsistenteConsulta::query()->count())->toBe(0);
});

it('does not call a compatible model whose url points at a private network', function () {
    Http::preventStrayRequests();

    $establecimiento = Establecimiento::factory()->create([
        'gemini_api_key' => 'clave-groq',
        'gemini_model' => 'llama',
        'asistente_proveedor' => 'compatible',
        'asistente_base_url' => 'https://10.0.0.1/v1',
    ]);

    expect(fn () => app(AsistenteCompatibleService::class)->generate('Hola', $establecimiento))
        ->toThrow(GeminiCredentialsMissingException::class, 'La URL base del asistente no está permitida. Revísala en Configuración.');

    Http::assertNothingSent();
});
