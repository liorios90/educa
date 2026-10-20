<?php

use App\Enums\Role;
use App\Models\Equivalencia;
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
    $admin = adminOf(Establecimiento::factory()->create());
    $equivalencia = Equivalencia::query()->create([
        'nombre' => 'Cualitativa MINEDUC',
        'activo' => true,
    ]);

    $this->actingAs($admin)
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
    $admin = adminOf(Establecimiento::factory()->create());
    $equivalencia = Equivalencia::query()->create([
        'nombre' => 'Cualitativa MINEDUC',
        'activo' => true,
    ]);

    $this->actingAs($admin)
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
