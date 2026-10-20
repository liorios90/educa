<?php

use App\Enums\TipoCalificacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoSubnivel;
use App\Models\Sys_Grado;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use Database\Seeders\BachilleratoTecnicoEducaSeeder;
use Database\Seeders\EstructuraEducativaSeeder;

it('uses the BGU courses and removes the provisional technical sublevel idempotently', function () {
    $this->seed(EstructuraEducativaSeeder::class);

    $establecimiento = Establecimiento::factory()->create([
        'nombre' => 'Instituto de Demostración 01',
        'codigo_amie' => '17HDEMO01',
    ]);
    $ofertaMatutina = ofertaDe($establecimiento, ['jornada' => 'Matutina']);
    $ofertaVespertina = ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
    $nivel = Sys_Nivel::query()->where('nombre', 'Bachillerato')->firstOrFail();
    $subnivelTecnico = Sys_Subnivel::factory()->for($nivel, 'nivel')->create([
        'nombre' => 'Bachillerato Técnico',
        'siglas' => 'BT',
        'tipo_calificacion' => TipoCalificacion::Cuantitativa,
    ]);

    EstablecimientoSubnivel::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $ofertaMatutina->id,
        'nivel_id' => $nivel->id,
        'subnivel_id' => $subnivelTecnico->id,
    ]);

    foreach ([
        ['nombre' => 'Primero de Bachillerato Técnico', 'siglas' => '1BT'],
        ['nombre' => 'Segundo de Bachillerato Técnico', 'siglas' => '2BT'],
        ['nombre' => 'Tercero de Bachillerato Técnico', 'siglas' => '3BT'],
    ] as $gradoData) {
        $gradoTecnico = Sys_Grado::factory()->for($subnivelTecnico, 'subnivel')->create($gradoData);
        EstablecimientoGrado::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $ofertaMatutina->id,
            'subnivel_id' => $subnivelTecnico->id,
            'grado_id' => $gradoTecnico->id,
        ]);
    }

    $this->seed(BachilleratoTecnicoEducaSeeder::class);
    $this->seed(BachilleratoTecnicoEducaSeeder::class);

    $subnivelBgu = Sys_Subnivel::query()->where('nombre', 'Bachillerato General Unificado')->firstOrFail();

    expect(Sys_Subnivel::query()->where('nombre', 'Bachillerato Técnico')->exists())->toBeFalse()
        ->and(Sys_Grado::query()->whereIn('nombre', [
            'Primero de Bachillerato Técnico',
            'Segundo de Bachillerato Técnico',
            'Tercero de Bachillerato Técnico',
        ])->exists())->toBeFalse();

    foreach ([$ofertaMatutina, $ofertaVespertina] as $oferta) {
        $this->assertDatabaseHas('establecimiento_subniveles', [
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'subnivel_id' => $subnivelBgu->id,
        ]);

        expect(EstablecimientoGrado::query()
            ->where('establecimiento_id', $establecimiento->id)
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)
            ->where('subnivel_id', $subnivelBgu->id)
            ->count())->toBe(3);
    }
});
