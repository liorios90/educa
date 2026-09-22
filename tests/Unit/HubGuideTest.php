<?php

use App\Models\NavigationItem;
use App\Navigation\HubGuide;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

it('explains catalogos for school staff', function () {
    expect(HubGuide::intro('Catálogos'))->toContain('matrícula');
});

it('groups catalog buttons with descriptions and icons', function () {
    $buttons = Collection::make([
        new NavigationItem(['label' => 'Jornadas', 'route_name' => 'sistemas.crud.jornadas.index', 'icon' => 'cog']),
        new NavigationItem(['label' => 'Zonas', 'route_name' => 'sistemas.crud.zonas.index', 'icon' => 'cog']),
        new NavigationItem(['label' => 'Funciones', 'route_name' => 'sistemas.crud.funciones.index', 'icon' => 'cog']),
    ]);

    $sections = HubGuide::sections($buttons);

    expect($sections)->toHaveCount(3)
        ->and($sections[0]['title'])->toBe('Jornada y modalidad')
        ->and($sections[0]['items'][0]['description'])->toContain('matutina')
        ->and($sections[0]['items'][0]['icon'])->toBe('clock')
        ->and($sections[1]['title'])->toBe('Ubicación de la institución')
        ->and($sections[2]['title'])->toBe('Personal de la institución');
});

it('omits the section title when every button belongs to the same group', function () {
    $buttons = Collection::make([
        new NavigationItem(['label' => 'Jornadas', 'route_name' => 'sistemas.crud.jornadas.index', 'icon' => 'cog']),
        new NavigationItem(['label' => 'Modalidades', 'route_name' => 'sistemas.crud.modalidades.index', 'icon' => 'cog']),
    ]);

    $sections = HubGuide::sections($buttons);

    expect($sections)->toHaveCount(1)
        ->and($sections[0]['title'])->toBe('');
});
