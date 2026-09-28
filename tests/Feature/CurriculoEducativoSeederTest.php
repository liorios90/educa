<?php

use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Subnivel;
use Database\Seeders\CurriculoEducativoSeeder;
use Database\Seeders\EstructuraEducativaSeeder;

it('seeds national areas and asignaturas for each ecuadorian subnivel', function () {
    $this->seed(EstructuraEducativaSeeder::class);
    $this->seed(CurriculoEducativoSeeder::class);

    $inicial2 = Sys_Subnivel::query()->where('nombre', 'Inicial 2')->firstOrFail();
    $elemental = Sys_Subnivel::query()->where('nombre', 'Básica Elemental')->firstOrFail();
    $superior = Sys_Subnivel::query()->where('nombre', 'Básica Superior')->firstOrFail();
    $bachillerato = Sys_Subnivel::query()->where('nombre', 'Bachillerato General Unificado')->firstOrFail();

    expect($inicial2->areas()->pluck('nombre')->all())->toBe([
        'Desarrollo personal y social',
        'Descubrimiento del medio natural y cultural',
        'Expresión y comunicación',
    ])
        ->and($inicial2->asignaturas()->pluck('sys_asignaturas.nombre')->all())->toContain('Identidad y autonomía')
        ->and($elemental->areas()->pluck('nombre')->all())->toContain('Matemática')
        ->and($elemental->asignaturas()->pluck('sys_asignaturas.nombre')->all())->toContain('Inglés')
        ->and($superior->asignaturas()->pluck('sys_asignaturas.nombre')->all())->toContain('Educación para la Ciudadanía')
        ->and($bachillerato->asignaturas()->pluck('sys_asignaturas.nombre')->all())->toContain('Filosofía')
        ->and($bachillerato->asignaturas()->pluck('sys_asignaturas.nombre')->all())->toContain('Física');

    $matematica = Sys_Asignatura::query()
        ->where('nombre', 'Matemática')
        ->whereHas('area', fn ($query) => $query->where('subnivel_id', $elemental->id))
        ->firstOrFail();

    expect($matematica->horas_semanales)->toBe(7)
        ->and($matematica->aparece_en_libreta)->toBeTrue();

    expect(Sys_Area::query()->count())->toBeGreaterThan(10)
        ->and(Sys_Asignatura::query()->count())->toBeGreaterThan(20);
});

it('does not fail when the educational structure has not been seeded', function () {
    $this->seed(CurriculoEducativoSeeder::class);

    expect(Sys_Area::query()->count())->toBe(0)
        ->and(Sys_Asignatura::query()->count())->toBe(0);
});
