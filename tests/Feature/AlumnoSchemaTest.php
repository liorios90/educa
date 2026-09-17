<?php

use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates the alumnos table', function () {
    expect(Schema::hasTable('alumnos'))->toBeTrue()
        ->and(Schema::hasColumns('alumnos', [
            'id',
            'persona_id',
            'contacto_emergencia',
            'usuario',
            'id_estructura_form_matricula',
            'padre_id',
            'activo',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('stores an alumno for a persona', function () {
    $pais = Sys_Pais::factory()->create();
    $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
    $personaId = DB::table('personas')->insertGetId([
        'tipo_identificacion_id' => 1,
        'identificacion' => '0912345679',
        'nombres' => 'Ana',
        'apellidos' => 'Pérez',
        'genero_id' => 2,
        'fecha_nacimiento' => '2012-05-10',
        'ciudad_nacimiento' => 'Guayaquil',
        'provincia_id' => $provincia->id,
        'parroquia' => 'Tarqui',
        'direccion' => 'Av. 9 de Octubre 200',
        'telefono1' => '042000001',
        'telefono2' => '0991111112',
        'nacionalidad_id' => $pais->id,
        'usuario' => 'admin',
        'activo' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $id = DB::table('alumnos')->insertGetId([
        'persona_id' => $personaId,
        'contacto_emergencia' => '0990000000',
        'usuario' => 'admin',
        'id_estructura_form_matricula' => 0,
        'activo' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->assertDatabaseHas('alumnos', [
        'id' => $id,
        'persona_id' => $personaId,
        'contacto_emergencia' => '0990000000',
        'usuario' => 'admin',
        'id_estructura_form_matricula' => 0,
        'activo' => 1,
    ]);
});
