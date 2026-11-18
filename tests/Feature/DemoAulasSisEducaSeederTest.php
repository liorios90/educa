<?php

use App\Enums\Role;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use App\Models\Persona;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Grado;
use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
use App\Models\User;
use Database\Seeders\DemoAulasSisEducaSeeder;
use Database\Seeders\RoleSeeder;

it('crea paralelos A y B y asigna docentes en Quinto, Octavo y Primero de Bachillerato', function () {
    $establecimiento = Establecimiento::factory()->create(['codigo_amie' => '01AS01061']);
    $oferta = EstablecimientoModalidadJornada::factory()->create([
        'establecimiento_modalidad_id' => EstablecimientoModalidad::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'modalidad_id' => Sys_Modalidad::factory()->create()->id,
        ])->id,
        'jornada_id' => Sys_Jornada::factory()->create()->id,
    ]);
    $periodo = EstablecimientoPeriodo::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'activo' => true,
    ]);
    $quinto = Sys_Grado::factory()->create(['nombre' => 'Quinto de EGB']);
    $segundo = Sys_Grado::factory()->create(['nombre' => 'Segundo de EGB']);
    $quintoInstitucional = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $quinto->id,
        'subnivel_id' => $quinto->subnivel_id,
    ]);
    $segundoInstitucional = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $segundo->id,
        'subnivel_id' => $segundo->subnivel_id,
    ]);
    $matematica = Sys_Asignatura::factory()->create(['nombre' => 'Matemática']);
    $malla = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $quinto->id,
        'asignatura_id' => $matematica->id,
    ]);

    $this->seed(RoleSeeder::class);
    $user = User::factory()->create([
        'email' => 'luis.aucapina@siseduca.test',
        'establecimiento_id' => $establecimiento->id,
    ]);
    $user->assignRole(Role::Docente);
    $persona = Persona::factory()->create([
        'user_id' => $user->id,
        'establecimiento_id' => $establecimiento->id,
    ]);
    $docente = Empleado::factory()->create(['persona_id' => $persona->id]);

    $this->seed(DemoAulasSisEducaSeeder::class);
    $this->seed(DemoAulasSisEducaSeeder::class);

    $aulasQuinto = EstablecimientoAula::query()
        ->where('establecimiento_periodo_id', $periodo->id)
        ->where('establecimiento_grado_id', $quintoInstitucional->id)
        ->orderBy('paralelo')
        ->get();

    expect($aulasQuinto->pluck('paralelo')->all())->toBe(['A', 'B'])
        ->and(EstablecimientoAula::query()->where('establecimiento_grado_id', $segundoInstitucional->id)->pluck('paralelo')->sort()->values()->all())->toBe(['A', 'B'])
        ->and(EstablecimientoAulaDocente::query()->whereIn('establecimiento_aula_id', $aulasQuinto->pluck('id'))->where('empleado_id', $docente->id)->count())->toBe(2)
        ->and(EstablecimientoAulaDocente::query()->where('establecimiento_asignatura_id', $malla->id)->count())->toBe(2)
        ->and(EstablecimientoAulaDocente::query()->whereHas('aula', fn ($query) => $query->where('establecimiento_grado_id', $segundoInstitucional->id))->count())->toBe(0)
        ->and($docente->especialidades()->whereKey($matematica->id)->exists())->toBeTrue();
});
