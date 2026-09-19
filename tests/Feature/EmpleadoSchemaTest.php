<?php

use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates the empleados table', function () {
    expect(Schema::hasTable('empleados'))->toBeTrue()
        ->and(Schema::hasColumns('empleados', [
            'id',
            'persona_id',
            'tipo_contrato_id',
            'cargo_id',
            'funcion_id',
            'horas',
            'anios_experiencia',
            'anios_instituto',
            'contacto_emergencia',
            'contacto_num',
            'usuario',
            'activo',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('stores an empleado for a persona', function () {
    $pais = Sys_Pais::factory()->create();
    $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
    $personaId = DB::table('personas')->insertGetId([
        'tipo_identificacion_id' => 1,
        'identificacion' => '0912345691',
        'nombres' => 'Luis',
        'apellidos' => 'Mora',
        'genero_id' => 1,
        'fecha_nacimiento' => '1985-03-12',
        'ciudad_nacimiento' => 'Quito',
        'provincia_id' => $provincia->id,
        'parroquia' => 'Iñaquito',
        'direccion' => 'Av. Amazonas 100',
        'telefono1' => '022000001',
        'telefono2' => '0991111113',
        'nacionalidad_id' => $pais->id,
        'usuario' => 'admin',
        'activo' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $tipoContratoId = DB::table('sys_tipo_contratos')->insertGetId([
        'codigo' => 'NOM',
        'nombre' => 'Nombramiento',
        'descripcion' => 'Contrato de nombramiento',
        'usuario' => 'admin',
        'activo' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $id = DB::table('empleados')->insertGetId([
        'persona_id' => $personaId,
        'tipo_contrato_id' => $tipoContratoId,
        'horas' => 40,
        'anios_experiencia' => 8,
        'anios_instituto' => 3,
        'contacto_emergencia' => 'María Mora',
        'contacto_num' => '0990000001',
        'usuario' => 'admin',
        'activo' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->assertDatabaseHas('empleados', [
        'id' => $id,
        'persona_id' => $personaId,
        'tipo_contrato_id' => $tipoContratoId,
        'horas' => 40,
        'anios_experiencia' => 8,
        'anios_instituto' => 3,
        'contacto_emergencia' => 'María Mora',
        'contacto_num' => '0990000001',
        'usuario' => 'admin',
        'activo' => 1,
    ]);
});
