<?php

use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use App\Models\HorarioClase;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Grado;
use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
use App\Models\Sys_Provincia;
use Database\Seeders\DemoMatriculasSisEducaSeeder;

it('matricula cinco estudiantes en cada aula con docente y horario', function () {
    Sys_Provincia::factory()->create(['nombre' => 'Azuay']);
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
    $catalogo = Sys_Grado::factory()->create(['nombre' => 'Quinto de EGB']);
    $grado = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $catalogo->id,
        'subnivel_id' => $catalogo->subnivel_id,
    ]);
    $malla = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $catalogo->id,
        'asignatura_id' => Sys_Asignatura::factory()->create()->id,
    ]);
    $conHorario = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $grado->id,
        'paralelo' => 'A',
    ]);
    $sinHorario = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $grado->id,
        'paralelo' => 'B',
    ]);
    $docente = Empleado::factory()->create();
    EstablecimientoAulaDocente::factory()->create([
        'establecimiento_aula_id' => $conHorario->id,
        'establecimiento_asignatura_id' => $malla->id,
        'empleado_id' => $docente->id,
    ]);
    HorarioClase::query()->create([
        'establecimiento_aula_id' => $conHorario->id,
        'establecimiento_asignatura_id' => $malla->id,
        'dia_semana' => 1,
        'hora_inicio' => '07:00',
        'hora_fin' => '07:45',
    ]);

    $this->seed(DemoMatriculasSisEducaSeeder::class);
    $this->seed(DemoMatriculasSisEducaSeeder::class);

    $hermanos = Alumno::query()
        ->whereHas('persona', fn ($query) => $query->where('apellidos', 'Arévalo Palacios'))
        ->get();

    expect(EstablecimientoMatricula::query()->where('establecimiento_aula_id', $conHorario->id)->count())->toBe(5)
        ->and(EstablecimientoMatricula::query()->where('establecimiento_aula_id', $sinHorario->id)->count())->toBe(0)
        ->and($hermanos)->toHaveCount(2)
        ->and($hermanos->pluck('padre_id')->unique())->toHaveCount(1)
        ->and($hermanos->every(fn (Alumno $alumno): bool => $alumno->matriculas()->where('establecimiento_aula_id', $conHorario->id)->exists()))->toBeTrue();
});
