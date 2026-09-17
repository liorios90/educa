<?php

use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates the padres table', function () {
    expect(Schema::hasTable('padres'))->toBeTrue()
        ->and(Schema::hasColumns('padres', [
            'id',
            'persona_id',
            'estado_civil_id',
            'vive_con_estudiante',
            'titulo',
            'usuario',
            'activo',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('stores a padre for a persona', function () {
    $pais = Sys_Pais::factory()->create();
    $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
    $personaId = DB::table('personas')->insertGetId([
        'tipo_identificacion_id' => 1,
        'identificacion' => '0912345678',
        'nombres' => 'Carlos',
        'apellidos' => 'Ruiz',
        'genero_id' => 1,
        'fecha_nacimiento' => '1975-03-20',
        'ciudad_nacimiento' => 'Guayaquil',
        'provincia_id' => $provincia->id,
        'parroquia' => 'Tarqui',
        'direccion' => 'Av. 9 de Octubre 100',
        'telefono1' => '042000000',
        'telefono2' => '0991111111',
        'nacionalidad_id' => $pais->id,
        'usuario' => 'admin',
        'activo' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $id = DB::table('padres')->insertGetId([
        'persona_id' => $personaId,
        'estado_civil_id' => 'casado',
        'vive_con_estudiante' => 1,
        'titulo' => 'Ingeniero',
        'usuario' => 'admin',
        'activo' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->assertDatabaseHas('padres', [
        'id' => $id,
        'persona_id' => $personaId,
        'estado_civil_id' => 'casado',
        'vive_con_estudiante' => 1,
        'titulo' => 'Ingeniero',
        'usuario' => 'admin',
        'activo' => 1,
    ]);
});
