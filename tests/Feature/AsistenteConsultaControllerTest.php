<?php

use App\Enums\Role;
use App\Models\AsistenteConsulta;
use App\Models\Establecimiento;
use App\Models\User;
use Database\Seeders\NavigationSeeder;
use Illuminate\Support\Facades\Http;

it('redirects guests from the AI query log to the login page', function () {
    $this->get(route('sistemas.asistente-consultas.index'))
        ->assertRedirect(route('login'));
});

it('forbids other roles from opening the AI query log', function (User $user) {
    $this->actingAs($user)
        ->get(route('sistemas.asistente-consultas.index'))
        ->assertForbidden();
})->with([
    'administrator' => fn () => adminOf(Establecimiento::factory()->create()),
    'secretary' => fn () => secretariaOf(Establecimiento::factory()->create()),
    'teacher' => fn () => assignRole(User::factory()->create([
        'establecimiento_id' => Establecimiento::factory()->create()->id,
    ]), Role::Docente),
]);

it('shows systems users every AI query with date, user and question', function () {
    $this->travelTo('2026-10-21 15:40:00');
    $establecimiento = Establecimiento::factory()->create(['nombre' => 'Unidad Educativa Andina']);
    $docente = assignRole(User::factory()->create([
        'name' => 'Ana Perez',
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Docente);
    $consulta = AsistenteConsulta::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'user_id' => $docente->id,
        'usuario' => 'Ana Perez',
        'pregunta' => '¿Cómo califico un insumo?',
        'created_at' => '2026-10-21 15:40:00',
    ]);
    $sistemas = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($sistemas)
        ->get(route('sistemas.asistente-consultas.index'))
        ->assertOk()
        ->assertSee('Consultas de la IA')
        ->assertSee('21/10/2026 15:40')
        ->assertSee('Ana Perez')
        ->assertSee('Unidad Educativa Andina')
        ->assertSee('¿Cómo califico un insumo?')
        ->assertSee('Eliminar')
        ->assertSee("dispatch('open-modal', 'consulta-{$consulta->id}')", false)
        ->assertSee('-webkit-line-clamp: 3', false);
});

it('shows an empty state when nobody has asked the assistant', function () {
    $sistemas = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($sistemas)
        ->get(route('sistemas.asistente-consultas.index'))
        ->assertOk()
        ->assertSee('Aún no hay consultas a la IA.');
});

it('records a teacher assistant question for the systems log', function () {
    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Usa la rúbrica del insumo.'],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'totalTokenCount' => 12,
            ],
        ]),
    ]);

    $establecimiento = Establecimiento::factory()->create([
        'nombre' => 'Colegio del Valle',
        'gemini_api_key' => 'clave-log',
        'gemini_model' => 'gemini-log',
    ]);
    $docente = assignRole(User::factory()->create([
        'name' => 'Luis Mora',
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Docente);
    $sistemas = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($docente)
        ->from(route('Docente.asistente'))
        ->post(route('Docente.asistente.store'), [
            'prompt' => '¿Cómo califico un insumo?',
        ])
        ->assertRedirect(route('Docente.asistente'));

    $this->assertDatabaseHas('asistente_consultas', [
        'establecimiento_id' => $establecimiento->id,
        'user_id' => $docente->id,
        'usuario' => 'Luis Mora',
        'pregunta' => '¿Cómo califico un insumo?',
    ]);

    $this->actingAs($sistemas)
        ->get(route('sistemas.asistente-consultas.index'))
        ->assertSee('Luis Mora')
        ->assertSee('Colegio del Valle')
        ->assertSee('¿Cómo califico un insumo?');
});

it('does not record a query when Gemini is not called', function () {
    Http::preventStrayRequests();
    $establecimiento = Establecimiento::factory()->create([
        'gemini_api_key' => null,
    ]);
    $docente = assignRole(User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Docente);

    $this->actingAs($docente)
        ->from(route('Docente.asistente'))
        ->post(route('Docente.asistente.store'), [
            'prompt' => '¿Cómo califico un insumo?',
        ])
        ->assertRedirect(route('Docente.asistente'));

    $this->assertDatabaseMissing('asistente_consultas', [
        'pregunta' => '¿Cómo califico un insumo?',
    ]);
});

it('escapes a dangerous question on the AI query log', function () {
    $establecimiento = Establecimiento::factory()->create([
        'nombre' => "<script>alert('colegio')</script>",
    ]);
    AsistenteConsulta::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'usuario' => "<script>alert('usuario')</script>",
        'pregunta' => "<script>alert('pregunta')</script>",
    ]);
    $sistemas = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($sistemas)
        ->get(route('sistemas.asistente-consultas.index'))
        ->assertSee("<script>alert('pregunta')</script>")
        ->assertDontSee("<script>alert('pregunta')</script>", false)
        ->assertSee("<script>alert('usuario')</script>")
        ->assertDontSee("<script>alert('usuario')</script>", false)
        ->assertSee("<script>alert('colegio')</script>")
        ->assertDontSee("<script>alert('colegio')</script>", false);
});

it('redirects guests from deleting an AI query to the login page', function () {
    $consulta = AsistenteConsulta::factory()->create();

    $this->delete(route('sistemas.asistente-consultas.destroy', $consulta))
        ->assertRedirect(route('login'));
});

it('forbids other roles from deleting an AI query', function (User $user) {
    $consulta = AsistenteConsulta::factory()->create();

    $this->actingAs($user)
        ->delete(route('sistemas.asistente-consultas.destroy', $consulta))
        ->assertForbidden();
})->with([
    'administrator' => fn () => adminOf(Establecimiento::factory()->create()),
    'secretary' => fn () => secretariaOf(Establecimiento::factory()->create()),
    'teacher' => fn () => assignRole(User::factory()->create([
        'establecimiento_id' => Establecimiento::factory()->create()->id,
    ]), Role::Docente),
]);

it('deletes an AI query from the systems log', function () {
    $consulta = AsistenteConsulta::factory()->create([
        'pregunta' => '¿Cómo califico un insumo?',
    ]);
    $sistemas = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($sistemas)
        ->from(route('sistemas.asistente-consultas.index'))
        ->delete(route('sistemas.asistente-consultas.destroy', $consulta))
        ->assertRedirect(route('sistemas.asistente-consultas.index'))
        ->assertSessionHas('status', 'consulta-eliminada');

    $this->assertDatabaseMissing('asistente_consultas', [
        'id' => $consulta->id,
    ]);
});

it('returns 404 when deleting a missing AI query', function () {
    $sistemas = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($sistemas)
        ->delete(route('sistemas.asistente-consultas.destroy', 999999))
        ->assertNotFound();
});

it('shows the AI query log link only to systems users', function () {
    $this->seed(NavigationSeeder::class);
    $sistemas = assignRole(User::factory()->create(), Role::Sistemas);
    $admin = adminOf(Establecimiento::factory()->create());

    $this->actingAs($sistemas)
        ->get(route('dashboard'))
        ->assertSee('Consultas IA')
        ->assertSee(route('sistemas.asistente-consultas.index'), false);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertDontSee(route('sistemas.asistente-consultas.index'), false);
});
