<?php

use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use App\Models\HorarioClase;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
use Database\Seeders\DemoHorariosSisEducaSeeder;

it('crea horarios solo en las aulas con docente y no los duplica', function () {
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
    $grado = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
    ]);
    $asignatura = Sys_Asignatura::factory()->create(['nombre' => 'Matemática']);
    $malla = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $grado->grado_id,
        'asignatura_id' => $asignatura->id,
        'horas_semanales' => 2,
    ]);
    $docente = Empleado::factory()->create();
    $conDocente = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $grado->id,
        'paralelo' => 'A',
    ]);
    $otro = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $grado->id,
        'paralelo' => 'B',
    ]);
    $vacia = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $grado->id,
        'paralelo' => 'C',
    ]);
    EstablecimientoAulaDocente::factory()->create([
        'establecimiento_aula_id' => $conDocente->id,
        'establecimiento_asignatura_id' => $malla->id,
        'empleado_id' => $docente->id,
    ]);
    EstablecimientoAulaDocente::factory()->create([
        'establecimiento_aula_id' => $otro->id,
        'establecimiento_asignatura_id' => $malla->id,
        'empleado_id' => $docente->id,
    ]);

    $this->seed(DemoHorariosSisEducaSeeder::class);
    $this->seed(DemoHorariosSisEducaSeeder::class);

    $clases = HorarioClase::query()
        ->whereIn('establecimiento_aula_id', [$conDocente->id, $otro->id])
        ->orderBy('dia_semana')
        ->orderBy('hora_inicio')
        ->get();

    expect($clases)->toHaveCount(4)
        ->and(HorarioClase::query()->where('establecimiento_aula_id', $vacia->id)->count())->toBe(0)
        ->and($clases->every(fn (HorarioClase $clase): bool => $clase->hora_fin === '07:45:00' || substr($clase->hora_fin, 0, 5) !== substr($clase->hora_inicio, 0, 5)))->toBeTrue();

    $cruce = $clases->contains(function (HorarioClase $clase) use ($clases): bool {
        return $clases->contains(fn (HorarioClase $otra): bool => $otra->id !== $clase->id
            && $otra->dia_semana === $clase->dia_semana
            && $otra->hora_inicio < $clase->hora_fin
            && $otra->hora_fin > $clase->hora_inicio);
    });

    expect($cruce)->toBeFalse();
});
