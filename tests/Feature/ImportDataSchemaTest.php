<?php

use App\Models\Establecimiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates the import_datas table', function () {
    expect(Schema::hasTable('import_datas'))->toBeTrue()
        ->and(Schema::hasColumns('import_datas', [
            'id',
            'nombre',
            'tablas',
            'tipo_archivo',
            'mensaje',
            'establecimiento_id',
            'usuario',
            'activo',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('stores import data for an establishment', function () {
    $establecimiento = Establecimiento::factory()->create();

    $id = DB::table('import_datas')->insertGetId([
        'nombre' => 'Estudiantes',
        'tablas' => 'estudiantes',
        'tipo_archivo' => 'xlsx',
        'mensaje' => 'Importación de estudiantes',
        'establecimiento_id' => $establecimiento->id,
        'usuario' => 'admin',
        'activo' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->assertDatabaseHas('import_datas', [
        'id' => $id,
        'nombre' => 'Estudiantes',
        'tablas' => 'estudiantes',
        'tipo_archivo' => 'xlsx',
        'mensaje' => 'Importación de estudiantes',
        'establecimiento_id' => $establecimiento->id,
        'usuario' => 'admin',
        'activo' => 1,
    ]);
});

it('creates the import_data_detalles table', function () {
    expect(Schema::hasTable('import_data_detalles'))->toBeTrue()
        ->and(Schema::hasColumns('import_data_detalles', [
            'id',
            'import_data_id',
            'num_fila',
            'identificacion',
            'descripcion',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('stores import data details for an import', function () {
    $establecimiento = Establecimiento::factory()->create();
    $importDataId = DB::table('import_datas')->insertGetId([
        'nombre' => 'Estudiantes',
        'tablas' => 'estudiantes',
        'tipo_archivo' => 'xlsx',
        'mensaje' => 'Importación de estudiantes',
        'establecimiento_id' => $establecimiento->id,
        'usuario' => 'admin',
        'activo' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $id = DB::table('import_data_detalles')->insertGetId([
        'import_data_id' => $importDataId,
        'num_fila' => 2,
        'identificacion' => '0102030405',
        'descripcion' => 'Fila con cédula duplicada',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->assertDatabaseHas('import_data_detalles', [
        'id' => $id,
        'import_data_id' => $importDataId,
        'num_fila' => 2,
        'identificacion' => '0102030405',
        'descripcion' => 'Fila con cédula duplicada',
    ]);
});

it('allows null values on import data detail optional columns', function () {
    $establecimiento = Establecimiento::factory()->create();
    $importDataId = DB::table('import_datas')->insertGetId([
        'nombre' => 'Estudiantes',
        'tablas' => 'estudiantes',
        'tipo_archivo' => 'xlsx',
        'mensaje' => 'Importación de estudiantes',
        'establecimiento_id' => $establecimiento->id,
        'usuario' => 'admin',
        'activo' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $id = DB::table('import_data_detalles')->insertGetId([
        'import_data_id' => $importDataId,
        'num_fila' => null,
        'identificacion' => null,
        'descripcion' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->assertDatabaseHas('import_data_detalles', [
        'id' => $id,
        'import_data_id' => $importDataId,
        'num_fila' => null,
        'identificacion' => null,
        'descripcion' => null,
    ]);
});
