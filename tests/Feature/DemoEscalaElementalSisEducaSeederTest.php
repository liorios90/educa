<?php

use App\Enums\CalificacionDestreza;
use App\Enums\TipoCalificacion;
use App\Models\Equivalencia;
use App\Models\Establecimiento;
use App\Models\EstablecimientoPeriodo;
use App\Models\TipoInsumo;
use Database\Seeders\DemoEscalaElementalSisEducaSeeder;

it('califica Básica Elemental con A+ a E- y deja A, EP e I para la libreta', function () {
    $establecimiento = Establecimiento::factory()->create(['codigo_amie' => '01AS01061']);
    $periodo = EstablecimientoPeriodo::factory()->create(['establecimiento_id' => $establecimiento->id]);
    $elemental = TipoInsumo::query()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'nombre' => 'Basica Elemental',
        'descripcion' => null,
        'tipo_calificacion' => TipoCalificacion::Cualitativa->value,
        'activo' => true,
    ]);
    $elemental->detalles()->create([
        'nombre' => 'Destreza o aprendizaje alcanzado',
        'valor' => 'A',
        'activo' => true,
    ]);
    $cuantitativa = TipoInsumo::query()->create([
        'establecimiento_periodo_id' => $periodo->id,
        'nombre' => 'Básica Media',
        'descripcion' => null,
        'tipo_calificacion' => TipoCalificacion::Cuantitativa->value,
        'activo' => true,
    ]);

    $this->seed(DemoEscalaElementalSisEducaSeeder::class);
    $this->seed(DemoEscalaElementalSisEducaSeeder::class);

    $activos = $elemental->detalles()->where('activo', true)->orderBy('id')->get();
    $equivalencia = Equivalencia::query()
        ->where('establecimiento_periodo_id', $periodo->id)
        ->where('nombre', 'Equivalencia cualitativa de Básica Elemental')
        ->firstOrFail();
    $libreta = $equivalencia->detalles()->orderBy('id')->get()->pluck('nombre', 'valor');

    expect($activos->pluck('valor')->all())->toBe(array_column(CalificacionDestreza::escalaElemental(), 'codigo'))
        ->and($elemental->detalles()->where('valor', 'A')->exists())->toBeFalse()
        ->and($activos)->toHaveCount(11)
        ->and($libreta->get('A+'))->toBe('A')
        ->and($libreta->get('A-'))->toBe('A')
        ->and($libreta->get('C+'))->toBe('EP')
        ->and($libreta->get('E-'))->toBe('I')
        ->and($libreta->get('NE'))->toBe('NE')
        ->and($equivalencia->detalles()->count())->toBe(11)
        ->and($cuantitativa->detalles()->count())->toBe(0);
});
