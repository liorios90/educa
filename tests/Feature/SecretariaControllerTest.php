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

it('lets a teacher open the assistant', function () {
    $docente = docenteDeEstablecimiento();

    $this->actingAs($docente)
        ->get(route('Docente.asistente'))
        ->assertOk()
        ->assertViewIs('secretaria.asistente')
        ->assertViewHas('storeRoute', route('Docente.asistente.store'))
        ->assertSee('Asistente')
        ->assertSee('Pregunta');
});

it('lets a secretary keep using the secretary assistant', function () {
    $secretaria = secretariaOf(Establecimiento::factory()->create());

    $this->actingAs($secretaria)
        ->get(route('secretaria.asistente'))
        ->assertOk()
        ->assertViewHas('storeRoute', route('secretaria.asistente.store'));
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
        ]),
    ]);

    $docente = docenteDeEstablecimiento();

    $this->actingAs($docente)
        ->from(route('Docente.asistente'))
        ->post(route('Docente.asistente.store'), [
            'prompt' => '¿Cómo califico un insumo?',
        ])
        ->assertRedirect(route('Docente.asistente'))
        ->assertSessionHas('respuesta', 'Respuesta de aula');

    Http::assertSent(function (Request $request) {
        return $request['contents'][0]['parts'][0]['text'] === '¿Cómo califico un insumo?';
    });
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
