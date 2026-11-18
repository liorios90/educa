<?php

use App\Enums\Role;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\Sys_Provincia;
use Database\Seeders\DemoEmpleadosSisEducaSeeder;

it('crea una secretaria y catorce docentes en Sis Educa', function () {
    Sys_Provincia::factory()->create(['nombre' => 'Azuay']);
    $establecimiento = Establecimiento::factory()->create(['codigo_amie' => '01AS01061']);

    $this->seed(DemoEmpleadosSisEducaSeeder::class);
    $this->seed(DemoEmpleadosSisEducaSeeder::class);

    $empleados = Empleado::query()
        ->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))
        ->with(['persona.user.roles', 'funcion', 'tipoContrato'])
        ->get();

    $secretaria = $empleados->first(fn (Empleado $empleado): bool => $empleado->persona?->nombres === 'Verónica Patricia');
    $docentes = $empleados->filter(fn (Empleado $empleado): bool => $empleado->persona?->user?->hasRole(Role::Docente->value));

    expect($empleados)->toHaveCount(15)
        ->and($secretaria)->not->toBeNull()
        ->and($secretaria?->funcion?->nombre)->toBe('Secretaría')
        ->and($secretaria?->persona?->user?->hasRole(Role::Secretaria->value))->toBeTrue()
        ->and($secretaria?->persona?->user?->hasRole(Role::Docente->value))->toBeFalse()
        ->and($docentes)->toHaveCount(14)
        ->and($docentes->every(fn (Empleado $empleado): bool => $empleado->funcion?->nombre === 'Docente'))->toBeTrue()
        ->and($empleados->pluck('tipoContrato.nombre')->unique()->sort()->values()->all())->toBe([
            'Contrato ocasional',
            'Nombramiento',
        ]);
});
