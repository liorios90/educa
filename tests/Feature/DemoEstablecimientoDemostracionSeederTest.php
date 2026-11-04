<?php

use App\Enums\EsquemaCiclo;
use App\Enums\Role;
use App\Models\Alumno;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoNivel;
use App\Models\EstablecimientoPeriodo;
use App\Models\Persona;
use App\Models\User;
use Database\Seeders\DemoEstablecimientoDemostracionSeeder;

it('crea Demostración con básica matutina, bachillerato vespertino y cinco alumnos por paralelo', function () {
    $this->seed(DemoEstablecimientoDemostracionSeeder::class);

    $establecimiento = Establecimiento::query()
        ->where('codigo_amie', DemoEstablecimientoDemostracionSeeder::CODIGO_AMIE)
        ->firstOrFail();
    $ofertas = EstablecimientoModalidadJornada::query()
        ->whereHas('establecimientoModalidad', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))
        ->with(['jornada', 'establecimientoModalidad.modalidad'])
        ->get();
    $matutina = $ofertas->first(fn (EstablecimientoModalidadJornada $oferta): bool => $oferta->jornada?->nombre === 'Matutina');
    $vespertina = $ofertas->first(fn (EstablecimientoModalidadJornada $oferta): bool => $oferta->jornada?->nombre === 'Vespertina');

    expect($establecimiento->nombre)->toBe('Demostración')
        ->and($matutina)->not->toBeNull()
        ->and($vespertina)->not->toBeNull();

    expect($matutina->establecimientoModalidad->modalidad->nombre)->toBe('Presencial')
        ->and($vespertina->establecimientoModalidad->modalidad->nombre)->toBe('Presencial')
        ->and(nombresDeNivel($matutina))->toBe(['Educación General Básica'])
        ->and(nombresDeNivel($vespertina))->toBe(['Bachillerato'])
        ->and(EstablecimientoGrado::query()->where('establecimiento_modalidad_jornada_id', $matutina->id)->count())->toBe(10)
        ->and(EstablecimientoGrado::query()->where('establecimiento_modalidad_jornada_id', $vespertina->id)->count())->toBe(3);

    foreach ([$matutina, $vespertina] as $oferta) {
        $periodo = EstablecimientoPeriodo::query()
            ->where('establecimiento_modalidad_jornada_id', $oferta->id)
            ->where('nombre', DemoEstablecimientoDemostracionSeeder::PERIODO)
            ->first();

        expect($periodo)->not->toBeNull()
            ->and($periodo->activo)->toBeTrue()
            ->and($periodo->esquema_ciclo)->toBe(EsquemaCiclo::Quimestres)
            ->and($periodo->ciclos()->count())->toBe(2);
    }

    $aulas = EstablecimientoAula::query()
        ->where('establecimiento_id', $establecimiento->id)
        ->with('establecimientoGrado')
        ->withCount('matriculas')
        ->get();
    $paralelosPorGrado = $aulas->groupBy('establecimiento_grado_id');

    expect($aulas)->toHaveCount(26)
        ->and($paralelosPorGrado)->toHaveCount(13)
        ->and($paralelosPorGrado->every(
            fn ($grupo): bool => $grupo->pluck('paralelo')->sort()->values()->all() === ['A', 'B'],
        ))->toBeTrue();

    foreach ($aulas as $aula) {
        $asignaturas = EstablecimientoAsignatura::query()
            ->where('establecimiento_modalidad_jornada_id', $aula->establecimiento_modalidad_jornada_id)
            ->where('grado_id', $aula->establecimientoGrado->grado_id)
            ->count();
        $docentes = EstablecimientoAulaDocente::query()
            ->where('establecimiento_aula_id', $aula->id)
            ->whereNotNull('empleado_id')
            ->count();

        expect($aula->matriculas_count)->toBe(5)
            ->and($asignaturas)->toBeGreaterThan(0)
            ->and($docentes)->toBe($asignaturas);
    }

    $admin = User::query()->where('email', DemoEstablecimientoDemostracionSeeder::ADMIN_EMAIL)->first();
    $alumno = User::query()->where('email', 'alumno-0955000001@demostracion.test')->first();
    $padre = User::query()->where('email', 'padre-0956000001@demostracion.test')->first();

    expect($admin?->hasRole(Role::Admin))->toBeTrue()
        ->and($admin?->establecimiento_id)->toBe($establecimiento->id)
        ->and($alumno?->hasRole(Role::Alumno))->toBeTrue()
        ->and($padre?->hasRole(Role::Padre))->toBeTrue()
        ->and(Alumno::query()->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))->whereNull('padre_id')->count())->toBe(0)
        ->and(EstablecimientoMatricula::query()->where('establecimiento_id', $establecimiento->id)->count())->toBe(130);

    $aulasAntes = $aulas->count();
    $matriculasAntes = 130;
    $personasAntes = Persona::query()->where('establecimiento_id', $establecimiento->id)->count();

    $this->seed(DemoEstablecimientoDemostracionSeeder::class);

    expect(EstablecimientoAula::query()->where('establecimiento_id', $establecimiento->id)->count())->toBe($aulasAntes)
        ->and(EstablecimientoMatricula::query()->where('establecimiento_id', $establecimiento->id)->count())->toBe($matriculasAntes)
        ->and(Persona::query()->where('establecimiento_id', $establecimiento->id)->count())->toBe($personasAntes);
});

/**
 * @return list<string>
 */
function nombresDeNivel(EstablecimientoModalidadJornada $oferta): array
{
    return EstablecimientoNivel::query()
        ->where('establecimiento_modalidad_jornada_id', $oferta->id)
        ->with('nivel')
        ->get()
        ->map(fn (EstablecimientoNivel $nivel): string => (string) $nivel->nivel?->nombre)
        ->sort()
        ->values()
        ->all();
}
