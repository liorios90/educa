<?php

use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
use Database\Seeders\ModalidadJornadaSeeder;

it('seeds ecuadorian modalidades and jornadas from the LOEI', function () {
    $this->seed(ModalidadJornadaSeeder::class);

    expect(Sys_Modalidad::query()->orderBy('id')->pluck('nombre')->all())->toBe([
        'Presencial',
        'Semipresencial',
        'Virtual',
    ]);

    expect(Sys_Jornada::query()->orderBy('id')->pluck('nombre')->all())->toBe([
        'Matutina',
        'Vespertina',
        'Nocturna',
        'Otro',
    ]);
});
