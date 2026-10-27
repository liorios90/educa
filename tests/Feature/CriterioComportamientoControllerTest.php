<?php

use App\Models\Establecimiento;
use App\Models\EstablecimientoPeriodo;
use App\Models\Sys_CriterioComportamiento;
use App\Models\User;

it('redirects guests from behavior criteria to the login page', function () {
    $this->get(route('Admin.criterios-comportamiento.edit'))
        ->assertRedirect(route('login'));
});

it('forbids a secretary from opening behavior criteria', function () {
    $secretaria = secretariaOf(Establecimiento::factory()->create());

    $this->actingAs($secretaria)
        ->get(route('Admin.criterios-comportamiento.edit'))
        ->assertForbidden();
});

it('explains the ministry rule and marks obligatory skills', function () {
    $contexto = contextoCriteriosComportamiento();

    $this->actingAs($contexto['admin'])
        ->withSession($contexto['sesion'])
        ->get(route('Admin.criterios-comportamiento.edit'))
        ->assertOk()
        ->assertSee('Acuerdo Ministerial MINEDEC-MINEDEC-2026-00082-A')
        ->assertSee('1 habilidad cognitiva, 3 sociales y 1 emocional')
        ->assertSee('COG-01')
        ->assertSee('Autoconocimiento')
        ->assertSee('Obligatorio')
        ->assertSee('Manejo de conflictos');
});

it('asks for an active period before choosing behavior criteria', function () {
    $admin = adminOf(Establecimiento::factory()->create());

    $this->actingAs($admin)
        ->get(route('Admin.criterios-comportamiento.edit'))
        ->assertOk()
        ->assertSee('Selecciona una oferta con un periodo activo para elegir los criterios de comportamiento.');

    $this->actingAs($admin)
        ->put(route('Admin.criterios-comportamiento.update'), [
            'criterios' => criteriosOpcionalesDeComportamiento(),
        ])
        ->assertNotFound();
});

it('saves the optional skills and keeps every active obligatory skill', function () {
    $contexto = contextoCriteriosComportamiento();

    $this->actingAs($contexto['admin'])
        ->withSession($contexto['sesion'])
        ->put(route('Admin.criterios-comportamiento.update'), [
            'criterios' => criteriosOpcionalesDeComportamiento(),
        ])
        ->assertRedirect(route('Admin.criterios-comportamiento.edit'))
        ->assertSessionHas('status', 'criterios-comportamiento-updated');

    expect(criteriosDelPeriodo($contexto['periodo']->id))->toBe([
        'COG-01',
        'COG-02',
        'COG-03',
        'COG-04',
        'EMO-01',
        'SOC-01',
        'SOC-02',
        'SOC-03',
        'SOC-05',
    ]);
});

it('replaces the optional skills of the period', function () {
    $contexto = contextoCriteriosComportamiento();
    $guardar = fn (array $codigos) => $this->actingAs($contexto['admin'])
        ->withSession($contexto['sesion'])
        ->put(route('Admin.criterios-comportamiento.update'), [
            'criterios' => idsDeCriterios($codigos),
        ])
        ->assertRedirect(route('Admin.criterios-comportamiento.edit'));

    $guardar(['COG-04', 'SOC-02', 'SOC-03', 'SOC-05', 'EMO-01']);
    $guardar(['COG-05', 'SOC-02', 'SOC-04', 'SOC-06', 'EMO-02']);

    expect(criteriosDelPeriodo($contexto['periodo']->id))->toBe([
        'COG-01',
        'COG-02',
        'COG-03',
        'COG-05',
        'EMO-02',
        'SOC-01',
        'SOC-02',
        'SOC-04',
        'SOC-06',
    ]);
});

it('rejects a selection that does not follow the ministry distribution', function () {
    $contexto = contextoCriteriosComportamiento();

    $this->actingAs($contexto['admin'])
        ->withSession($contexto['sesion'])
        ->from(route('Admin.criterios-comportamiento.edit'))
        ->put(route('Admin.criterios-comportamiento.update'), [
            'criterios' => idsDeCriterios(['COG-04', 'COG-05', 'SOC-02', 'SOC-03', 'SOC-05', 'EMO-01']),
        ])
        ->assertRedirect(route('Admin.criterios-comportamiento.edit'))
        ->assertSessionHasErrors([
            'criterios' => 'Además de los criterios obligatorios, elige 1 habilidad cognitiva, 3 sociales y 1 emocional.',
        ]);

    expect(criteriosDelPeriodo($contexto['periodo']->id))->toBe([]);
});

it('rejects an inactive behavior criterion', function () {
    $contexto = contextoCriteriosComportamiento();
    $inactivo = Sys_CriterioComportamiento::factory()->inactivo()->create();

    $this->actingAs($contexto['admin'])
        ->withSession($contexto['sesion'])
        ->from(route('Admin.criterios-comportamiento.edit'))
        ->put(route('Admin.criterios-comportamiento.update'), [
            'criterios' => [$inactivo->id],
        ])
        ->assertRedirect(route('Admin.criterios-comportamiento.edit'))
        ->assertSessionHasErrors([
            'criterios.0' => 'Selecciona solo criterios activos del catálogo.',
        ]);

    expect(criteriosDelPeriodo($contexto['periodo']->id))->toBe([]);
});

it('does not force an obligatory skill that sistemas deactivated', function () {
    $contexto = contextoCriteriosComportamiento();
    Sys_CriterioComportamiento::query()->where('codigo', 'COG-01')->update(['activo' => false]);

    $this->actingAs($contexto['admin'])
        ->withSession($contexto['sesion'])
        ->put(route('Admin.criterios-comportamiento.update'), [
            'criterios' => criteriosOpcionalesDeComportamiento(),
        ])
        ->assertRedirect(route('Admin.criterios-comportamiento.edit'));

    expect(criteriosDelPeriodo($contexto['periodo']->id))->toBe([
        'COG-02',
        'COG-03',
        'COG-04',
        'EMO-01',
        'SOC-01',
        'SOC-02',
        'SOC-03',
        'SOC-05',
    ]);
});

it('keeps the behavior criteria of another establishment', function () {
    $propio = contextoCriteriosComportamiento();
    $ajeno = contextoCriteriosComportamiento();

    $this->actingAs($propio['admin'])
        ->withSession($propio['sesion'])
        ->put(route('Admin.criterios-comportamiento.update'), [
            'criterios' => idsDeCriterios(['COG-04', 'SOC-02', 'SOC-03', 'SOC-05', 'EMO-01']),
        ])
        ->assertRedirect(route('Admin.criterios-comportamiento.edit'));

    $this->actingAs($ajeno['admin'])
        ->withSession($ajeno['sesion'])
        ->put(route('Admin.criterios-comportamiento.update'), [
            'criterios' => idsDeCriterios(['COG-05', 'SOC-04', 'SOC-06', 'SOC-07', 'EMO-02']),
        ])
        ->assertRedirect(route('Admin.criterios-comportamiento.edit'));

    expect(criteriosDelPeriodo($propio['periodo']->id))->toBe([
        'COG-01', 'COG-02', 'COG-03', 'COG-04', 'EMO-01', 'SOC-01', 'SOC-02', 'SOC-03', 'SOC-05',
    ])->and(criteriosDelPeriodo($ajeno['periodo']->id))->toBe([
        'COG-01', 'COG-02', 'COG-03', 'COG-05', 'EMO-02', 'SOC-01', 'SOC-04', 'SOC-06', 'SOC-07',
    ]);
});

it('escapes a behavior criterion description', function () {
    $contexto = contextoCriteriosComportamiento();
    Sys_CriterioComportamiento::factory()->create([
        'descripcion' => "<script>alert('xss')</script>",
    ]);

    $this->actingAs($contexto['admin'])
        ->withSession($contexto['sesion'])
        ->get(route('Admin.criterios-comportamiento.edit'))
        ->assertSee("<script>alert('xss')</script>")
        ->assertDontSee("<script>alert('xss')</script>", false);
});

/**
 * @return array{admin: User, periodo: EstablecimientoPeriodo, sesion: array<string, int>}
 */
function contextoCriteriosComportamiento(): array
{
    $establecimiento = Establecimiento::factory()->create();
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);

    return [
        'admin' => adminOf($establecimiento),
        'periodo' => $periodo,
        'sesion' => activeContextSession($oferta, $periodo),
    ];
}

/**
 * @param  list<string>  $codigos
 * @return list<int>
 */
function idsDeCriterios(array $codigos): array
{
    return Sys_CriterioComportamiento::query()
        ->whereIn('codigo', $codigos)
        ->orderBy('codigo')
        ->pluck('id')
        ->map(fn (mixed $id): int => (int) $id)
        ->all();
}

/**
 * @return list<int>
 */
function criteriosOpcionalesDeComportamiento(): array
{
    return idsDeCriterios(['COG-04', 'SOC-02', 'SOC-03', 'SOC-05', 'EMO-01']);
}

/**
 * @return list<string>
 */
function criteriosDelPeriodo(int $periodoId): array
{
    return Sys_CriterioComportamiento::query()
        ->whereHas(
            'periodos',
            fn ($query) => $query->whereKey($periodoId),
        )
        ->orderBy('codigo')
        ->pluck('codigo')
        ->all();
}
