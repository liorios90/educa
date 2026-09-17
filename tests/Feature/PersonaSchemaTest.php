<?php

use App\Models\Establecimiento;
use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates the personas table', function () {
    expect(Schema::hasTable('personas'))->toBeTrue()
        ->and(Schema::hasColumns('personas', [
            'id',
            'user_id',
            'tipo_identificacion_id',
            'identificacion',
            'nombres',
            'apellidos',
            'genero_id',
            'fecha_nacimiento',
            'ciudad_nacimiento',
            'provincia_id',
            'parroquia',
            'direccion',
            'telefono1',
            'telefono2',
            'nacionalidad_id',
            'usuario',
            'activo',
            'establecimiento_id',
            'lote',
            'created_at',
            'updated_at',
        ]))->toBeTrue();

    $identificacionIndex = collect(Schema::getIndexes('personas'))
        ->first(fn (array $index): bool => in_array('identificacion', $index['columns'], true));

    expect($identificacionIndex)->not->toBeNull()
        ->and($identificacionIndex['unique'])->toBeTrue();
});

it('stores a persona for an establishment', function () {
    $pais = Sys_Pais::factory()->create();
    $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
    $establecimiento = Establecimiento::factory()->create();

    $id = DB::table('personas')->insertGetId([
        'tipo_identificacion_id' => 1,
        'identificacion' => '0102030405',
        'nombres' => 'Ana',
        'apellidos' => 'Pérez',
        'genero_id' => 1,
        'fecha_nacimiento' => '1990-05-12',
        'ciudad_nacimiento' => 'Cuenca',
        'provincia_id' => $provincia->id,
        'parroquia' => 'El Sagrario',
        'direccion' => 'Av. Solano 123',
        'telefono1' => '072800000',
        'telefono2' => '0990000000',
        'nacionalidad_id' => $pais->id,
        'usuario' => 'admin',
        'activo' => 1,
        'establecimiento_id' => $establecimiento->id,
        'lote' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->assertDatabaseHas('personas', [
        'id' => $id,
        'identificacion' => '0102030405',
        'nombres' => 'Ana',
        'apellidos' => 'Pérez',
        'provincia_id' => $provincia->id,
        'nacionalidad_id' => $pais->id,
        'establecimiento_id' => $establecimiento->id,
        'lote' => 1,
    ]);
});

it('allows null values on persona optional columns', function () {
    $pais = Sys_Pais::factory()->create();
    $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);

    $id = DB::table('personas')->insertGetId([
        'tipo_identificacion_id' => 1,
        'identificacion' => '1712345678',
        'nombres' => 'Luis',
        'apellidos' => 'Mora',
        'genero_id' => 1,
        'fecha_nacimiento' => null,
        'ciudad_nacimiento' => 'Quito',
        'provincia_id' => $provincia->id,
        'parroquia' => 'Centro',
        'direccion' => 'Calle 10',
        'telefono1' => '022000000',
        'telefono2' => '0980000000',
        'nacionalidad_id' => $pais->id,
        'usuario' => 'admin',
        'activo' => 1,
        'establecimiento_id' => null,
        'lote' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->assertDatabaseHas('personas', [
        'id' => $id,
        'identificacion' => '1712345678',
        'fecha_nacimiento' => null,
        'establecimiento_id' => null,
        'lote' => null,
    ]);
});
