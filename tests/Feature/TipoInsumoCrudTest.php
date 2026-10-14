<?php

use App\Models\Establecimiento;
use App\Models\TipoInsumo;

it('allows an admin to create, list, update, and delete types of supply', function (): void {
    $admin = adminOf(Establecimiento::factory()->create());

    $this->actingAs($admin)
        ->post(route('Admin.tipos-insumos.store'), [
            'nombre' => 'Tarea',
            'descripcion' => 'Trabajo de aula',
            'tipo_calificacion' => 'cuantitativa',
            'activo' => '1',
        ])
        ->assertRedirect(route('Admin.tipos-insumos.index'));

    $tipoInsumo = TipoInsumo::query()->firstOrFail();
    $this->assertDatabaseHas('tipos_insumos', ['id' => $tipoInsumo->id, 'nombre' => 'Tarea']);

    $this->actingAs($admin)
        ->get(route('Admin.tipos-insumos.index'))
        ->assertOk()
        ->assertSee('Tarea')
        ->assertSee('Cuantitativa');

    $this->actingAs($admin)
        ->patch(route('Admin.tipos-insumos.update', $tipoInsumo), [
            'nombre' => 'Proyecto',
            'descripcion' => 'Proyecto final',
            'tipo_calificacion' => 'cualitativa',
            'activo' => '0',
        ])
        ->assertRedirect(route('Admin.tipos-insumos.index'));

    $this->assertDatabaseHas('tipos_insumos', ['id' => $tipoInsumo->id, 'nombre' => 'Proyecto', 'activo' => false]);

    $this->actingAs($admin)
        ->delete(route('Admin.tipos-insumos.destroy', $tipoInsumo))
        ->assertRedirect(route('Admin.tipos-insumos.index'));

    $this->assertDatabaseMissing('tipos_insumos', ['id' => $tipoInsumo->id]);
});

it('rejects an unsupported grading type', function (): void {
    $admin = adminOf(Establecimiento::factory()->create());

    $this->actingAs($admin)
        ->from(route('Admin.tipos-insumos.create'))
        ->post(route('Admin.tipos-insumos.store'), [
            'nombre' => 'Tarea',
            'tipo_calificacion' => 'mixta',
            'activo' => '1',
        ])
        ->assertSessionHasErrors('tipo_calificacion');

    $this->assertDatabaseCount('tipos_insumos', 0);
});

it('stores qualitative detail values on the same detail screen', function (): void {
    $admin = adminOf(Establecimiento::factory()->create());
    $tipoInsumo = TipoInsumo::query()->create([
        'nombre' => 'Evaluación cualitativa',
        'tipo_calificacion' => 'cualitativa',
        'activo' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('Admin.tipos-insumos.detalles.store', $tipoInsumo), [
            'nombre' => 'A+',
            'descripcion' => 'Destreza alcanzada',
            'valor' => 'A+',
            'activo' => '1',
        ])
        ->assertRedirect(route('Admin.tipos-insumos.detalles.index', $tipoInsumo));

    $this->assertDatabaseHas('tipos_insumos_detalles', [
        'tipo_insumo_id' => $tipoInsumo->id,
        'nombre' => 'A+',
        'valor' => 'A+',
        'porcentaje' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('Admin.tipos-insumos.detalles.index', $tipoInsumo))
        ->assertOk()
        ->assertSee('Detalles creados')
        ->assertSee('Destreza alcanzada');
});

it('shows the quantitative active percentage total without blocking incomplete or excessive totals', function (): void {
    $admin = adminOf(Establecimiento::factory()->create());
    $tipoInsumo = TipoInsumo::query()->create([
        'nombre' => 'Evaluación cuantitativa',
        'tipo_calificacion' => 'cuantitativa',
        'activo' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('Admin.tipos-insumos.detalles.store', $tipoInsumo), [
            'nombre' => 'Tareas',
            'porcentaje' => '60',
            'activo' => '1',
        ])
        ->assertRedirect();

    $activo = $tipoInsumo->detalles()->where('nombre', 'Tareas')->firstOrFail();
    $this->actingAs($admin)
        ->post(route('Admin.tipos-insumos.detalles.store', $tipoInsumo), [
            'nombre' => 'Inactivo',
            'porcentaje' => '99',
            'activo' => '0',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->get(route('Admin.tipos-insumos.detalles.index', $tipoInsumo))
        ->assertOk()
        ->assertSee('Falta')
        ->assertSee('40.00')
        ->assertSee('99.00');

    $this->actingAs($admin)
        ->patch(route('Admin.tipos-insumos.detalles.update', [$tipoInsumo, $activo]), [
            'nombre' => 'Tareas',
            'porcentaje' => '100',
            'activo' => '1',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('Admin.tipos-insumos.detalles.store', $tipoInsumo), [
            'nombre' => 'Examen',
            'porcentaje' => '1',
            'activo' => '1',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->get(route('Admin.tipos-insumos.detalles.index', $tipoInsumo))
        ->assertOk()
        ->assertSee('supera el 100%')
        ->assertSee('1.00');
});
