<?php

use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Subnivel;
use Illuminate\Support\Facades\Schema;

it('creates the sys_areas table', function () {
    expect(Schema::hasTable('sys_areas'))->toBeTrue()
        ->and(Schema::hasColumns('sys_areas', [
            'id',
            'codigo',
            'nombre',
            'descripcion',
            'orden',
            'aparece_en_libreta',
            'subnivel_id',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('stores an area for a subnivel', function () {
    $subnivel = Sys_Subnivel::factory()->create();
    $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create([
        'codigo' => 'MAT',
        'nombre' => 'Matemática',
        'descripcion' => 'Área de matemática',
        'orden' => 1,
        'aparece_en_libreta' => true,
    ]);

    $this->assertDatabaseHas('sys_areas', [
        'id' => $area->id,
        'codigo' => 'MAT',
        'nombre' => 'Matemática',
        'descripcion' => 'Área de matemática',
        'orden' => 1,
        'aparece_en_libreta' => true,
        'subnivel_id' => $subnivel->id,
    ]);
});

it('creates the sys_asignaturas table', function () {
    expect(Schema::hasTable('sys_asignaturas'))->toBeTrue()
        ->and(Schema::hasColumns('sys_asignaturas', [
            'id',
            'codigo',
            'nombre',
            'descripcion',
            'orden',
            'horas_semanales',
            'aparece_en_libreta',
            'area_id',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('stores an asignatura for an area', function () {
    $area = Sys_Area::factory()->create();
    $asignatura = Sys_Asignatura::factory()->for($area, 'area')->create([
        'codigo' => 'ALG',
        'nombre' => 'Álgebra',
        'descripcion' => 'Álgebra de básica',
        'orden' => 1,
        'horas_semanales' => 5,
        'aparece_en_libreta' => true,
    ]);

    $this->assertDatabaseHas('sys_asignaturas', [
        'id' => $asignatura->id,
        'codigo' => 'ALG',
        'nombre' => 'Álgebra',
        'descripcion' => 'Álgebra de básica',
        'orden' => 1,
        'horas_semanales' => 5,
        'aparece_en_libreta' => true,
        'area_id' => $area->id,
    ]);
});
