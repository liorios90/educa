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
use Database\Seeders\DemoAulasDocentesSeeder;
use Database\Seeders\RoleSeeder;

it('crea el paralelo A para cada grado y asigna docentes a sus asignaturas de forma repetible', function () {
    $establecimiento = Establecimiento::factory()->create(['codigo_amie' => '01H-F001']);
    $modalidad = Sys_Modalidad::factory()->create();
    $jornada = Sys_Jornada::factory()->create();
    $asignatura = Sys_Asignatura::factory()->create();
    $establecimientoModalidad = EstablecimientoModalidad::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'modalidad_id' => $modalidad->id,
    ]);
    $oferta = EstablecimientoModalidadJornada::factory()->create([
        'establecimiento_modalidad_id' => $establecimientoModalidad->id,
        'jornada_id' => $jornada->id,
    ]);
    $periodo = EstablecimientoPeriodo::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'activo' => true,
    ]);
    $grado = Sys_Grado::factory()->create();
    $gradoInstitucional = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $grado->id,
        'subnivel_id' => $grado->subnivel_id,
    ]);
    $malla = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $grado->id,
        'asignatura_id' => $asignatura->id,
    ]);

    $this->seed(RoleSeeder::class);
    $user = User::factory()->create(['establecimiento_id' => $establecimiento->id]);
    $user->assignRole(Role::Docente);
    $persona = Persona::factory()->create([
        'user_id' => $user->id,
        'establecimiento_id' => $establecimiento->id,
    ]);
    $docente = Empleado::factory()->create(['persona_id' => $persona->id]);

    $this->seed(DemoAulasDocentesSeeder::class);
    $this->seed(DemoAulasDocentesSeeder::class);

    $aula = EstablecimientoAula::query()
        ->where('establecimiento_periodo_id', $periodo->id)
        ->where('establecimiento_grado_id', $gradoInstitucional->id)
        ->where('paralelo', 'A')
        ->firstOrFail();

    expect(EstablecimientoAula::query()->where('establecimiento_grado_id', $gradoInstitucional->id)->count())->toBe(1)
        ->and(EstablecimientoAulaDocente::query()
            ->where('establecimiento_aula_id', $aula->id)
            ->where('establecimiento_asignatura_id', $malla->id)
            ->where('empleado_id', $docente->id)
            ->count())->toBe(1);
});
