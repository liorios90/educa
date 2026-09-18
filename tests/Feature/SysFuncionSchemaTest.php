<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates the sys_funciones table', function () {
    expect(Schema::hasTable('sys_funciones'))->toBeTrue()
        ->and(Schema::hasColumns('sys_funciones', [
            'id',
            'nombre',
            'descripcion',
            'usuario',
            'activo',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('stores a funcion', function () {
    $id = DB::table('sys_funciones')->insertGetId([
        'nombre' => 'Docente',
        'descripcion' => 'Función docente',
        'usuario' => 'admin',
        'activo' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->assertDatabaseHas('sys_funciones', [
        'id' => $id,
        'nombre' => 'Docente',
        'descripcion' => 'Función docente',
        'usuario' => 'admin',
        'activo' => 1,
    ]);
});
