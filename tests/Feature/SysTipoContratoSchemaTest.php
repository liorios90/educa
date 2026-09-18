<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates the sys_tipo_contratos table', function () {
    expect(Schema::hasTable('sys_tipo_contratos'))->toBeTrue()
        ->and(Schema::hasColumns('sys_tipo_contratos', [
            'id',
            'codigo',
            'nombre',
            'descripcion',
            'usuario',
            'activo',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('stores a tipo de contrato', function () {
    $id = DB::table('sys_tipo_contratos')->insertGetId([
        'codigo' => 'NOM',
        'nombre' => 'Nombramiento',
        'descripcion' => 'Contrato de nombramiento',
        'usuario' => 'admin',
        'activo' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->assertDatabaseHas('sys_tipo_contratos', [
        'id' => $id,
        'codigo' => 'NOM',
        'nombre' => 'Nombramiento',
        'descripcion' => 'Contrato de nombramiento',
        'usuario' => 'admin',
        'activo' => 1,
    ]);
});
