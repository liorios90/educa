<?php

use App\Models\Alumno;
use App\Models\Establecimiento;
use App\Models\Padre;
use App\Models\Persona;
use App\Models\Sys_Provincia;
use Database\Seeders\DemoFamiliasSisEducaSeeder;

it('crea nueve padres y diez estudiantes en Sis Educa, con dos hermanos', function () {
    $provincia = Sys_Provincia::factory()->create(['nombre' => 'Azuay']);
    $establecimiento = Establecimiento::factory()->create(['codigo_amie' => '01AS01061']);

    $this->seed(DemoFamiliasSisEducaSeeder::class);
    $this->seed(DemoFamiliasSisEducaSeeder::class);

    $padres = Padre::query()
        ->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))
        ->withCount('alumnos')
        ->get();

    expect($padres)->toHaveCount(9)
        ->and($padres->pluck('alumnos_count')->sort()->values()->all())->toBe([1, 1, 1, 1, 1, 1, 1, 1, 2])
        ->and(Alumno::query()->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))->count())->toBe(10)
        ->and(Persona::query()->where('provincia_id', $provincia->id)->count())->toBe(19);

    $hermanos = Alumno::query()
        ->whereHas('persona', fn ($query) => $query->where('apellidos', 'Villacís Cárdenas')->where('establecimiento_id', $establecimiento->id))
        ->with('persona')
        ->get();

    expect($hermanos)->toHaveCount(2)
        ->and($hermanos->pluck('padre_id')->unique())->toHaveCount(1)
        ->and($hermanos->pluck('persona.nombres')->sort()->values()->all())->toBe([
            'Mateo Alejandro',
            'Sofía Valentina',
        ]);
});
