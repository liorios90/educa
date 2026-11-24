<?php

use App\Enums\TipoCalificacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Grado;
use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
use App\Models\TipoInsumo;
use App\Services\AcompanamientoCivica;
use Database\Seeders\DemoCivicaSisEducaSeeder;
use Illuminate\Support\Facades\DB;

it('crea la evaluación cualitativa de Cívica y la asigna solo a esa asignatura', function () {
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
    $catalogo = Sys_Grado::factory()->create(['nombre' => 'Octavo de EGB']);
    $grado = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $catalogo->id,
        'subnivel_id' => $catalogo->subnivel_id,
    ]);
    $civica = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $catalogo->id,
        'asignatura_id' => Sys_Asignatura::factory()->create([
            'nombre' => 'Cívica y Acompañamiento Integral en el Aula',
        ])->id,
        'tipo_calificacion' => TipoCalificacion::Cuantitativa,
    ]);
    $matematica = EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'grado_id' => $catalogo->id,
        'asignatura_id' => Sys_Asignatura::factory()->create(['nombre' => 'Matemática'])->id,
        'tipo_calificacion' => TipoCalificacion::Cuantitativa,
    ]);
    $aula = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'establecimiento_periodo_id' => $periodo->id,
        'establecimiento_grado_id' => $grado->id,
        'paralelo' => 'A',
    ]);

    $this->seed(DemoCivicaSisEducaSeeder::class);
    $this->seed(DemoCivicaSisEducaSeeder::class);

    $tipo = TipoInsumo::query()
        ->where('establecimiento_periodo_id', $periodo->id)
        ->where('nombre', 'Cívica y Acompañamiento Integral en el Aula')
        ->firstOrFail();

    expect($tipo->tipo_calificacion)->toBe(TipoCalificacion::Cualitativa->value)
        ->and($tipo->detalles()->orderBy('id')->pluck('valor')->all())->toBe(['A', 'EP', 'I', 'NE'])
        ->and($civica->refresh()->tipo_calificacion)->toBe(TipoCalificacion::Cualitativa)
        ->and($civica->califica_por_destrezas)->toBeTrue()
        ->and($matematica->refresh()->tipo_calificacion)->toBe(TipoCalificacion::Cuantitativa)
        ->and(DB::table('establecimiento_aula_asignatura_tipo_insumos')->where('establecimiento_aula_id', $aula->id)->where('establecimiento_asignatura_id', $civica->id)->value('tipo_insumo_id'))->toBe($tipo->id)
        ->and(DB::table('establecimiento_aula_asignatura_tipo_insumos')->where('establecimiento_asignatura_id', $matematica->id)->count())->toBe(0)
        ->and(app(AcompanamientoCivica::class)->codigosElegidos($establecimiento->id))->toBe(['CIV.03', 'CIV.06', 'CIV.09', 'CIV.12', 'CIV.14']);
});
