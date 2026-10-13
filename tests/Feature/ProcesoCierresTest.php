<?php

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\EstablecimientoPeriodoCicloParcial;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

function datosProcesoCierres(): array
{
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta, [
        'nombre_esquema' => 'Trimestre',
        'numero_parciales' => 2,
        'abierto_en' => now(),
    ]);

    $primerCiclo = EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'orden' => 1,
        'nombre' => 'Primer trimestre',
    ]);
    $segundoCiclo = EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'orden' => 2,
        'nombre' => 'Segundo trimestre',
    ]);

    $parciales = collect([$primerCiclo, $segundoCiclo])->flatMap(fn (EstablecimientoPeriodoCiclo $ciclo) => collect([1, 2])->map(fn (int $numero) => EstablecimientoPeriodoCicloParcial::factory()->create([
        'establecimiento_periodo_ciclo_id' => $ciclo->id,
        'numero' => $numero,
    ])));

    return compact('establecimiento', 'admin', 'oferta', 'periodo', 'parciales');
}

it('renders the three closure tabs and partials grouped by cycle', function () {
    $datos = datosProcesoCierres();

    $this->actingAs($datos['admin'])
        ->withSession(activeContextSession($datos['oferta'], $datos['periodo']))
        ->get(route('Admin.proceso-cierres.index'))
        ->assertSee('Parciales')
        ->assertSee('estructura-folders', false)
        ->assertSee('estructura-folder-panel', false)
        ->assertSee('Trimestre')
        ->assertSee('Periodo')
        ->assertSee('En construcción.')
        ->assertSee('Ciclo 1')
        ->assertSee('Ciclo 2')
        ->assertSee('Primer trimestre')
        ->assertSee('Segundo trimestre')
        ->assertSee('Parcial 1')
        ->assertSee('Parcial 2')
        ->assertSee('Cerrar parcial')
        ->assertSee('name="parciales_cerrados[]"', false)
        ->assertDontSee('Lista de parciales')
        ->assertDontSee('even:bg-sky-50', false)
        ->assertDontSee('Habilitado Examen');
});

it('saves only the checked parcial closures for the active period', function () {
    $datos = datosProcesoCierres();
    $parcialCerrado = $datos['parciales'][0];
    $parcialPendiente = $datos['parciales'][1];

    $this->actingAs($datos['admin'])
        ->withSession(activeContextSession($datos['oferta'], $datos['periodo']))
        ->put(route('Admin.proceso-cierres.update'), [
            'parciales_cerrados' => [(string) $parcialCerrado->id],
        ])
        ->assertRedirect(route('Admin.proceso-cierres.index'))
        ->assertSessionHas('status', 'cierres-parciales-updated');

    expect($parcialCerrado->fresh()->cerrado_en)->not->toBeNull();
    expect($parcialPendiente->fresh()->cerrado_en)->toBeNull();
});

it('rejects closing a parcial outside the active period', function () {
    $datos = datosProcesoCierres();
    $otraOferta = ofertaDe($datos['establecimiento'], ['jornada' => 'Vespertina']);
    $otroPeriodo = periodoActivoDe($otraOferta, ['abierto_en' => now()]);
    $otroCiclo = EstablecimientoPeriodoCiclo::factory()->create([
        'establecimiento_periodo_id' => $otroPeriodo->id,
        'orden' => 1,
    ]);
    $parcialAjeno = EstablecimientoPeriodoCicloParcial::factory()->create([
        'establecimiento_periodo_ciclo_id' => $otroCiclo->id,
    ]);

    $this->actingAs($datos['admin'])
        ->withSession(activeContextSession($datos['oferta'], $datos['periodo']))
        ->from(route('Admin.proceso-cierres.index'))
        ->put(route('Admin.proceso-cierres.update'), [
            'parciales_cerrados' => [(string) $parcialAjeno->id],
        ])
        ->assertSessionHasErrors('parciales_cerrados.0');

    expect($parcialAjeno->fresh()->cerrado_en)->toBeNull();
});

it('forbids users without the administrator role from opening closure processes', function () {
    $user = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($user)
        ->get(route('Admin.proceso-cierres.index'))
        ->assertForbidden();
});

it('adds the closure process to the administrator sidebar', function () {
    $this->seed(NavigationSeeder::class);
    $admin = assignRole(User::factory()->create(), Role::Admin);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertSee('Proceso de Cierres')
        ->assertSee(route('Admin.proceso-cierres.index'), false);
});
