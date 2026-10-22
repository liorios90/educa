<?php

use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\Padre;
use App\Models\Sys_Funcion;
use App\Models\Sys_Provincia;
use Database\Seeders\DemoMantenimientoSeeder;

it('crea quince padres, alumnos y docentes en Demo_Formal y puede repetirse', function () {
    $establecimiento = Establecimiento::factory()->create(['codigo_amie' => '01H-F001']);
    Sys_Provincia::factory()->create();

    $this->seed(DemoMantenimientoSeeder::class);
    $this->seed(DemoMantenimientoSeeder::class);

    expect(Padre::query()->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))->count())->toBe(15)
        ->and(Alumno::query()->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))->count())->toBe(15)
        ->and(Empleado::query()->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))->count())->toBe(15)
        ->and(Empleado::query()->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))
            ->where('funcion_id', Sys_Funcion::query()->where('nombre', 'Docente')->value('id'))
            ->count())->toBe(15)
        ->and(Alumno::query()->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))
            ->whereNull('padre_id')->count())->toBe(0);
});
