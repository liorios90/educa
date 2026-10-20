<?php

use App\Exceptions\GeminiCredentialsMissingException;
use App\Models\Establecimiento;
use App\Services\GeminiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('returns the generated text from Gemini using the school credentials', function () {
    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Hola desde el fake'],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $establecimiento = Establecimiento::factory()->create([
        'gemini_api_key' => 'clave-andina',
        'gemini_model' => 'gemini-andina',
    ]);

    $respuesta = app(GeminiService::class)->generate('Resume este tema', $establecimiento);

    expect($respuesta->texto)->toBe('Hola desde el fake')
        ->and($respuesta->tokens)->toBe(0);

    Http::assertSent(function (Request $request) {
        return $request->hasHeader('x-goog-api-key', 'clave-andina')
            && str_contains($request->url(), 'models/gemini-andina:generateContent')
            && $request['contents'][0]['parts'][0]['text'] === 'Resume este tema';
    });
});

it('does not use another school key when generating a response', function () {
    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Respuesta propia'],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    Establecimiento::factory()->create([
        'gemini_api_key' => 'clave-ajena',
        'gemini_model' => 'modelo-ajeno',
    ]);
    $establecimiento = Establecimiento::factory()->create([
        'gemini_api_key' => 'clave-propia',
        'gemini_model' => 'modelo-propio',
    ]);

    app(GeminiService::class)->generate('Hola', $establecimiento);

    Http::assertSent(function (Request $request) {
        return $request->hasHeader('x-goog-api-key', 'clave-propia')
            && str_contains($request->url(), 'models/modelo-propio:generateContent');
    });
});

it('falls back to the configured model when the school leaves it empty', function () {
    config(['services.gemini.model' => 'gemini-3.6-flash']);
    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Modelo por defecto'],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $establecimiento = Establecimiento::factory()->create([
        'gemini_api_key' => 'clave-andina',
        'gemini_model' => null,
    ]);

    app(GeminiService::class)->generate('Hola', $establecimiento);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'models/gemini-3.6-flash:generateContent'));
});

it('rejects a prompt when the school has no Gemini key', function () {
    Http::preventStrayRequests();

    $establecimiento = Establecimiento::factory()->create([
        'gemini_api_key' => null,
    ]);

    expect(fn () => app(GeminiService::class)->generate('Hola', $establecimiento))
        ->toThrow(GeminiCredentialsMissingException::class);

    Http::assertNothingSent();
});

it('reads the token count returned by Gemini', function () {
    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Con tokens'],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'totalTokenCount' => 42,
            ],
        ]),
    ]);

    $establecimiento = Establecimiento::factory()->create([
        'gemini_api_key' => 'clave-andina',
        'gemini_model' => 'gemini-andina',
    ]);

    $respuesta = app(GeminiService::class)->generate('Hola', $establecimiento);

    expect($respuesta->tokens)->toBe(42);
});
