<?php

use App\Enums\Role;
use App\Models\Establecimiento;
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
