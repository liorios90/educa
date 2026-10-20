<?php

use App\Auth\ActiveRole;
use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\User;
use Database\Seeders\NavigationSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function docenteDeEstablecimiento(): User
{
    $establecimiento = Establecimiento::factory()->create();

    return assignRole(User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Docente);
}

it('redirects guests from the teacher assistant to the login page', function (string $method, string $routeName) {
    $this->{$method}(route($routeName))
        ->assertRedirect(route('login'));
})->with([
    'open page' => ['get', 'Docente.asistente'],
    'send prompt' => ['post', 'Docente.asistente.store'],
]);

it('forbids other roles from opening the teacher assistant', function (User $user) {
    $this->actingAs($user)
        ->get(route('Docente.asistente'))
        ->assertForbidden();
})->with([
    'administrator' => fn () => adminOf(Establecimiento::factory()->create()),
    'secretary' => fn () => secretariaOf(Establecimiento::factory()->create()),
]);

it('does not show another school usage on the teacher assistant', function () {
    $this->travelTo('2026-10-20 12:00:00');
    Establecimiento::factory()->create([
        'gemini_preguntas_usadas' => 50,
        'gemini_tokens_usados' => 400,
        'gemini_uso_mes' => '2026-10',
        'gemini_preguntas_limite' => 80,
        'gemini_tokens_limite' => 500,
    ]);
    $docente = docenteDeEstablecimiento();

    $this->actingAs($docente)
        ->get(route('Docente.asistente'))
        ->assertSee('Quedan 100 de 100')
        ->assertDontSee('Quedan 30 de 80')
        ->assertDontSee('Quedan 100 de 500');
});

it('lets a teacher open the assistant', function () {
    $this->travelTo('2026-10-20 12:00:00');
    $docente = docenteDeEstablecimiento();

    $this->actingAs($docente)
        ->get(route('Docente.asistente'))
        ->assertOk()
        ->assertViewIs('secretaria.asistente')
        ->assertViewHas('storeRoute', route('Docente.asistente.store'))
        ->assertSee('Asistente')
        ->assertSee('Pregunta')
        ->assertSee('Este instituto aún no tiene una clave de Gemini')
        ->assertSee('Preguntas')
        ->assertSee('Tokens')
        ->assertSee('octubre 2026')
        ->assertSee('Quedan 100 de 100')
        ->assertSee('Quedan 100.000 de 100.000');
});

it('lets a secretary keep using the secretary assistant', function () {
    $this->travelTo('2026-10-20 12:00:00');
    $establecimiento = Establecimiento::factory()->create([
        'gemini_preguntas_usadas' => 4,
        'gemini_tokens_usados' => 80,
        'gemini_uso_mes' => '2026-10',
        'gemini_preguntas_limite' => 100,
        'gemini_tokens_limite' => 1000,
    ]);
    $secretaria = secretariaOf($establecimiento);

    $this->actingAs($secretaria)
        ->get(route('secretaria.asistente'))
        ->assertOk()
        ->assertViewHas('storeRoute', route('secretaria.asistente.store'))
        ->assertSee('Quedan 96 de 100')
        ->assertSee('Quedan 920 de 1.000');
});

it('shows the assistant link to teachers', function () {
    $this->seed(NavigationSeeder::class);
    $docente = docenteDeEstablecimiento();

    $this->actingAs($docente)
        ->withSession([ActiveRole::SESSION_KEY => Role::Docente->value])
        ->get(route('dashboard'))
        ->assertSee('Asistente')
        ->assertSee(route('Docente.asistente'), false);
});

it('lets a teacher send a prompt to the assistant', function () {
    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Respuesta de aula'],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'totalTokenCount' => 25,
            ],
        ]),
    ]);

    $docente = docenteDeEstablecimiento();
    $docente->establecimiento->update([
        'gemini_api_key' => 'clave-docente',
        'gemini_model' => 'gemini-docente',
        'gemini_preguntas_limite' => 100,
        'gemini_tokens_limite' => 1000,
    ]);

    $this->actingAs($docente)
        ->from(route('Docente.asistente'))
        ->post(route('Docente.asistente.store'), [
            'prompt' => '¿Cómo califico un insumo?',
        ])
        ->assertRedirect(route('Docente.asistente'))
        ->assertSessionHas('respuesta', 'Respuesta de aula');

    Http::assertSent(function (Request $request) {
        return $request->hasHeader('x-goog-api-key', 'clave-docente')
            && str_contains($request->url(), 'models/gemini-docente:generateContent')
            && $request['contents'][0]['parts'][0]['text'] === '¿Cómo califico un insumo?';
    });

    $docente->establecimiento->refresh();

    expect($docente->establecimiento->gemini_preguntas_usadas)->toBe(1)
        ->and($docente->establecimiento->gemini_tokens_usados)->toBe(25);

    $this->actingAs($docente)
        ->get(route('Docente.asistente'))
        ->assertSee('Quedan 99 de 100')
        ->assertSee('Quedan 975 de 1.000');
});

it('does not call Gemini when the school used every question this month', function () {
    Http::preventStrayRequests();
    $this->travelTo('2026-10-20 12:00:00');

    $docente = docenteDeEstablecimiento();
    $docente->establecimiento->update([
        'gemini_api_key' => 'clave-docente',
        'gemini_preguntas_limite' => 2,
        'gemini_tokens_limite' => 1000,
        'gemini_preguntas_usadas' => 2,
        'gemini_tokens_usados' => 10,
        'gemini_uso_mes' => '2026-10',
    ]);

    $this->actingAs($docente)
        ->from(route('Docente.asistente'))
        ->post(route('Docente.asistente.store'), [
            'prompt' => '¿Cómo califico un insumo?',
        ])
        ->assertRedirect(route('Docente.asistente'))
        ->assertSessionHas('error', 'Este instituto ya no tiene preguntas disponibles este mes. Pide al administrador que aumente el cupo en Configuración → Asistente.');

    Http::assertNothingSent();
});

it('does not call Gemini when the teacher school has no key', function () {
    Http::preventStrayRequests();

    $docente = docenteDeEstablecimiento();

    $this->actingAs($docente)
        ->from(route('Docente.asistente'))
        ->post(route('Docente.asistente.store'), [
            'prompt' => '¿Cómo califico un insumo?',
        ])
        ->assertRedirect(route('Docente.asistente'))
        ->assertSessionHas('error', 'Este instituto no tiene configurada la clave de Gemini. Pide al administrador que la ingrese en Configuración.');

    Http::assertNothingSent();
});

it('redirects back when the teacher prompt is missing', function () {
    $docente = docenteDeEstablecimiento();

    $this->actingAs($docente)
        ->from(route('Docente.asistente'))
        ->post(route('Docente.asistente.store'), [
            'prompt' => '',
        ])
        ->assertRedirect(route('Docente.asistente'))
        ->assertSessionHasErrors('prompt');
});

it('escapes a dangerous assistant answer', function () {
    $docente = docenteDeEstablecimiento();

    $this->actingAs($docente)
        ->withSession(['respuesta' => "<script>alert('xss')</script>"])
        ->get(route('Docente.asistente'))
        ->assertDontSee("<script>alert('xss')</script>", false);
});
