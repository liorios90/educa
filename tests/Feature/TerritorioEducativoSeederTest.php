<?php

use App\Models\Sys_Circuito;
use App\Models\Sys_Distrito;
use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;
use App\Models\Sys_Zona;
use Database\Seeders\TerritorioEducativoSeeder;

it('loads ecuador provinces onto the existing country and the ministry zones and districts', function () {
    $ecuador = Sys_Pais::factory()->create([
        'nombre' => 'Ecuador',
        'descripcion' => 'País ya registrado',
    ]);
    Sys_Pais::factory()->create(['nombre' => 'Colombia']);

    $this->seed(TerritorioEducativoSeeder::class);

    expect(Sys_Pais::query()->where('nombre', 'Ecuador')->count())->toBe(1)
        ->and($ecuador->fresh()->descripcion)->toBe('País ya registrado')
        ->and(Sys_Provincia::query()->where('pais_id', $ecuador->id)->count())->toBe(24)
        ->and(Sys_Provincia::query()->where('pais_id', '!=', $ecuador->id)->count())->toBe(0);

    expect(Sys_Provincia::query()->pluck('nombre')->all())->toEqualCanonicalizing([
        'Azuay',
        'Bolívar',
        'Cañar',
        'Carchi',
        'Chimborazo',
        'Cotopaxi',
        'El Oro',
        'Esmeraldas',
        'Galápagos',
        'Guayas',
        'Imbabura',
        'Loja',
        'Los Ríos',
        'Manabí',
        'Morona Santiago',
        'Napo',
        'Orellana',
        'Pastaza',
        'Pichincha',
        'Santa Elena',
        'Santo Domingo de los Tsáchilas',
        'Sucumbíos',
        'Tungurahua',
        'Zamora Chinchipe',
    ]);

    expect(Sys_Zona::query()->orderBy('nombre')->pluck('nombre')->all())->toBe([
        'Zona 1',
        'Zona 2',
        'Zona 3',
        'Zona 4',
        'Zona 5',
        'Zona 6',
        'Zona 7',
        'Zona 8',
        'Zona 9',
    ]);

    expect(Sys_Distrito::query()->count())->toBe(140);

    $zona2 = Sys_Zona::query()->where('nombre', 'Zona 2')->firstOrFail();
    $zona5 = Sys_Zona::query()->where('nombre', 'Zona 5')->firstOrFail();
    $zona8 = Sys_Zona::query()->where('nombre', 'Zona 8')->firstOrFail();
    $zona9 = Sys_Zona::query()->where('nombre', 'Zona 9')->firstOrFail();

    expect($zona9->distritos()->orderBy('nombre')->pluck('nombre')->all())->toBe([
        '17D01 Nanegal - Nanegalito',
        '17D02 Calderón',
        '17D03 La Delicia',
        '17D04 Centro',
        '17D05 Norte',
        '17D06 Eloy Alfaro',
        '17D07 Quitumbe',
        '17D08 Los Chillos',
        '17D09 Tumbaco',
    ])
        ->and($zona2->distritos()->where('nombre', 'like', '17D10 %')->value('provincia'))->toBe('Pichincha')
        ->and($zona8->distritos()->where('nombre', '09D01 Ximena 1')->value('provincia'))->toBe('Guayas')
        ->and($zona8->distritos()->count())->toBe(12)
        ->and($zona5->distritos()->where('nombre', '20D01 San Cristóbal - Isabela - Santa Cruz')->value('provincia'))->toBe('Galápagos');

    expect(Sys_Circuito::query()->count())->toBe(1130);

    $tulcan = Sys_Distrito::query()->where('nombre', 'like', '04D01 %')->firstOrFail();
    $huaca = Sys_Circuito::query()->where('nombre', '04D01C01 Huaca')->firstOrFail();
    $ximena = Sys_Distrito::query()->where('nombre', '09D01 Ximena 1')->firstOrFail();

    expect($huaca->distrito_id)->toBe($tulcan->id)
        ->and(Sys_Circuito::query()->where('nombre', '09D01C01 Guayaquil-Guasmo')->value('distrito_id'))->toBe($ximena->id)
        ->and(Sys_Circuito::query()->where('nombre', '08D03C03 Salima')->value('distrito_id'))->toBe(
            Sys_Distrito::query()->where('nombre', 'like', '08D03 %')->value('id'),
        );
});

it('does not duplicate official territory when seeded again', function () {
    $this->seed(TerritorioEducativoSeeder::class);
    $this->seed(TerritorioEducativoSeeder::class);

    expect(Sys_Pais::query()->whereRaw('lower(nombre) = ?', ['ecuador'])->count())->toBe(1)
        ->and(Sys_Provincia::query()->count())->toBe(24)
        ->and(Sys_Zona::query()->count())->toBe(9)
        ->and(Sys_Distrito::query()->count())->toBe(140)
        ->and(Sys_Circuito::query()->count())->toBe(1130);
});
