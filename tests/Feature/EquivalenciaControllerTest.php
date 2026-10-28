<?php

use App\Enums\Role;
use App\Enums\UsoEquivalencia;
use App\Models\Equivalencia;
use App\Models\EquivalenciaDetalle;
use App\Models\Establecimiento;
use App\Models\EstablecimientoComportamiento;
use App\Models\User;

it('redirects guests to the login page', function () {
    $this->get(route('Admin.tablas-equivalencias.index'))
        ->assertRedirect(route('login'));
});

it('forbids a teacher from opening equivalence tables', function () {
    $docente = assignRole(User::factory()->create([
        'establecimiento_id' => Establecimiento::factory()->create()->id,
    ]), Role::Docente);

    $this->actingAs($docente)
        ->get(route('Admin.tablas-equivalencias.index'))
        ->assertForbidden();
});

it('creates an equivalence detail with a numeric range', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $equivalencia = equivalenciaDe($periodo);
    $sesion = activeContextSession($oferta, $periodo);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->from(route('Admin.tablas-equivalencias.detalles.index', $equivalencia))
        ->post(route('Admin.tablas-equivalencias.detalles.store', $equivalencia), [
            'nombre' => 'A',
            'valor' => 'A',
            'minimo' => '9',
            'maximo' => '10',
            'activo' => '1',
        ])
        ->assertRedirect(route('Admin.tablas-equivalencias.detalles.index', $equivalencia))
        ->assertSessionHas('status', 'equivalencia-detalle-created');

    $this->assertDatabaseHas('equivalencias_detalles', [
        'equivalencia_id' => $equivalencia->id,
        'nombre' => 'A',
        'valor' => 'A',
        'minimo' => 9,
        'maximo' => 10,
    ]);
});

it('redirects back when the minimum is greater than the maximum', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $equivalencia = equivalenciaDe($periodo);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->from(route('Admin.tablas-equivalencias.detalles.index', $equivalencia))
        ->post(route('Admin.tablas-equivalencias.detalles.store', $equivalencia), [
            'nombre' => 'A',
            'valor' => 'A',
            'minimo' => '10',
            'maximo' => '9',
            'activo' => '1',
        ])
        ->assertRedirect(route('Admin.tablas-equivalencias.detalles.index', $equivalencia))
        ->assertSessionHasErrors(['maximo' => 'El mínimo no puede ser mayor que el máximo.']);

    $this->assertDatabaseCount('equivalencias_detalles', 0);
});

it('keeps equivalence tables scoped to the active period', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $otroPeriodo = periodoActivoDe($oferta, ['nombre' => 'Periodo anterior', 'activo' => false]);
    $sesion = activeContextSession($oferta, $periodo);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->post(route('Admin.tablas-equivalencias.store'), [
            'nombre' => 'Tabla del periodo activo',
            'descripcion' => 'MINEDUC',
            'uso' => 'asignatura',
            'activo' => '1',
        ])
        ->assertRedirect(route('Admin.tablas-equivalencias.index'));

    $this->assertDatabaseHas('equivalencias', [
        'nombre' => 'Tabla del periodo activo',
        'establecimiento_periodo_id' => $periodo->id,
    ]);

    $ajena = equivalenciaDe($otroPeriodo, ['nombre' => 'Tabla del periodo anterior']);

    $this->actingAs($admin)
        ->withSession($sesion)
        ->get(route('Admin.tablas-equivalencias.index'))
        ->assertOk()
        ->assertSee('Tabla del periodo activo')
        ->assertSee($periodo->nombre)
        ->assertDontSee('Tabla del periodo anterior');

    $this->actingAs($admin)
        ->withSession($sesion)
        ->get(route('Admin.tablas-equivalencias.edit', $ajena))
        ->assertNotFound();
});

it('creates the reglamento behavior scale once for the active period', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->post(route('Admin.tablas-equivalencias.comportamiento.store'))
        ->assertRedirect();

    $escala = Equivalencia::query()->where('uso', UsoEquivalencia::Comportamiento)->first();

    expect($escala)->not->toBeNull()
        ->and($escala->detalles)->toHaveCount(4)
        ->and($escala->detalles->firstWhere('valor', 'O')?->exige_observacion)->toBeTrue()
        ->and($escala->detalles->firstWhere('valor', 'S')?->exige_observacion)->toBeFalse()
        ->and($escala->detalles->firstWhere('valor', 'S')?->jerarquia)->toBe(4)
        ->and($escala->detalles->firstWhere('nombre', 'Siempre'))->not->toBeNull()
        ->and($escala->detalles->firstWhere('valor', 'N')?->jerarquia)->toBe(1);

    $this->post(route('Admin.tablas-equivalencias.comportamiento.store'))
        ->assertRedirect(route('Admin.tablas-equivalencias.detalles.index', $escala));

    expect(Equivalencia::query()->where('uso', UsoEquivalencia::Comportamiento)->count())->toBe(1);
});

it('stores a behavior letter without a numeric range', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $equivalencia = equivalenciaDe($periodo, [
        'nombre' => 'Escala institucional',
        'uso' => UsoEquivalencia::Comportamiento,
    ]);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->from(route('Admin.tablas-equivalencias.detalles.index', $equivalencia))
        ->post(route('Admin.tablas-equivalencias.detalles.store', $equivalencia), [
            'nombre' => 'Muy bueno',
            'descripcion' => 'Cumple de forma destacada los indicadores de la asignatura.',
            'valor' => 'MB',
            'jerarquia' => '6',
            'exige_observacion' => '1',
            'activo' => '1',
        ])
        ->assertRedirect(route('Admin.tablas-equivalencias.detalles.index', $equivalencia));

    $this->assertDatabaseHas('equivalencias_detalles', [
        'equivalencia_id' => $equivalencia->id,
        'valor' => 'MB',
        'jerarquia' => 6,
        'exige_observacion' => true,
        'minimo' => null,
        'maximo' => null,
    ]);
});

it('rejects a behavior letter without a code', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $equivalencia = equivalenciaDe($periodo, ['uso' => UsoEquivalencia::Comportamiento]);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->from(route('Admin.tablas-equivalencias.detalles.index', $equivalencia))
        ->post(route('Admin.tablas-equivalencias.detalles.store', $equivalencia), [
            'nombre' => 'Muy bueno',
            'exige_observacion' => '0',
            'activo' => '1',
        ])
        ->assertRedirect(route('Admin.tablas-equivalencias.detalles.index', $equivalencia))
        ->assertSessionHasErrors(['valor' => 'Escribe el código de la calificación.']);
});

it('rejects a behavior grade without a hierarchical value', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $equivalencia = equivalenciaDe($periodo, ['uso' => UsoEquivalencia::Comportamiento]);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->from(route('Admin.tablas-equivalencias.detalles.index', $equivalencia))
        ->post(route('Admin.tablas-equivalencias.detalles.store', $equivalencia), [
            'nombre' => 'Muy bueno',
            'descripcion' => 'Cumple los indicadores.',
            'valor' => 'MB',
            'exige_observacion' => '0',
            'activo' => '1',
        ])
        ->assertRedirect(route('Admin.tablas-equivalencias.detalles.index', $equivalencia))
        ->assertSessionHasErrors(['jerarquia' => 'Escribe el valor jerárquico. El más alto corresponde al mejor cumplimiento.']);
});

it('rejects two behavior grades with the same hierarchical value', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $equivalencia = equivalenciaDe($periodo, ['uso' => UsoEquivalencia::Comportamiento]);
    EquivalenciaDetalle::query()->create([
        'equivalencia_id' => $equivalencia->id,
        'nombre' => 'Satisfactorio',
        'descripcion' => 'Cumple los indicadores.',
        'valor' => 'B',
        'jerarquia' => 4,
        'activo' => true,
        'exige_observacion' => false,
    ]);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->from(route('Admin.tablas-equivalencias.detalles.index', $equivalencia))
        ->post(route('Admin.tablas-equivalencias.detalles.store', $equivalencia), [
            'nombre' => 'Otro satisfactorio',
            'descripcion' => 'También cumple los indicadores.',
            'valor' => 'B2',
            'jerarquia' => '4',
            'exige_observacion' => '0',
            'activo' => '1',
        ])
        ->assertRedirect(route('Admin.tablas-equivalencias.detalles.index', $equivalencia))
        ->assertSessionHasErrors(['jerarquia' => 'Ese valor jerárquico ya está en la tabla.']);
});

it('rejects a second behavior table in the same period', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    equivalenciaDe($periodo, ['uso' => UsoEquivalencia::Comportamiento, 'nombre' => 'Comportamiento']);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->from(route('Admin.tablas-equivalencias.index'))
        ->post(route('Admin.tablas-equivalencias.store'), [
            'nombre' => 'Otra escala',
            'uso' => 'comportamiento',
            'activo' => '1',
        ])
        ->assertRedirect(route('Admin.tablas-equivalencias.index'))
        ->assertSessionHasErrors(['uso' => 'Este periodo ya tiene una tabla de comportamiento.']);
});

it('hides subject assignment for a behavior table', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $equivalencia = equivalenciaDe($periodo, ['uso' => UsoEquivalencia::Comportamiento]);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->get(route('Admin.tablas-equivalencias.asignaturas.index', $equivalencia))
        ->assertNotFound();
});

it('keeps a behavior letter that students already received', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);
    $equivalencia = equivalenciaDe($periodo, ['uso' => UsoEquivalencia::Comportamiento]);
    $detalle = EquivalenciaDetalle::query()->create([
        'equivalencia_id' => $equivalencia->id,
        'nombre' => 'Satisfactorio',
        'valor' => 'B',
        'activo' => true,
        'exige_observacion' => false,
    ]);
    EstablecimientoComportamiento::factory()->create([
        'equivalencia_detalle_id' => $detalle->id,
    ]);

    $this->actingAs($admin)
        ->withSession(activeContextSession($oferta, $periodo))
        ->from(route('Admin.tablas-equivalencias.detalles.index', $equivalencia))
        ->delete(route('Admin.tablas-equivalencias.detalles.destroy', [$equivalencia, $detalle]))
        ->assertRedirect(route('Admin.tablas-equivalencias.detalles.index', $equivalencia))
        ->assertSessionHasErrors([
            'detalle' => 'Esta calificación ya fue usada en un aula. Desactívala en lugar de eliminarla.',
        ]);

    $this->assertModelExists($detalle);
});
