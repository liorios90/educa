<?php

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use App\Models\Sys_Grado;
use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function assignRole(User $user, Role $role): User
{
    RoleModel::findOrCreate($role->value, 'web');
    $user->assignRole($role);

    return $user;
}

function adminOf(Establecimiento $establecimiento): User
{
    return assignRole(User::factory()->create([
        'name' => 'Director Andino',
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Admin);
}

/**
 * @return array{nivel: Sys_Nivel, subnivel: Sys_Subnivel, grado: Sys_Grado}
 */
function catalogoEstructura(array $nombres = []): array
{
    $nivel = Sys_Nivel::factory()->create(['nombre' => $nombres['nivel'] ?? 'Educación Inicial']);
    $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create(['nombre' => $nombres['subnivel'] ?? 'Inicial 2']);
    $grado = Sys_Grado::factory()->for($subnivel, 'subnivel')->create(['nombre' => $nombres['grado'] ?? 'Segundo de inicial']);

    return compact('nivel', 'subnivel', 'grado');
}

function ofertaDe(Establecimiento $establecimiento, array $nombres = []): EstablecimientoModalidadJornada
{
    $modalidadNombre = $nombres['modalidad'] ?? 'Presencial';
    $catalogoModalidad = Sys_Modalidad::query()->where('nombre', $modalidadNombre)->first()
        ?? Sys_Modalidad::factory()->create(['nombre' => $modalidadNombre]);

    $establecimientoModalidad = EstablecimientoModalidad::query()->firstOrCreate([
        'establecimiento_id' => $establecimiento->id,
        'modalidad_id' => $catalogoModalidad->id,
    ]);

    $jornadaNombre = $nombres['jornada'] ?? 'Matutina';
    $catalogoJornada = Sys_Jornada::query()->where('nombre', $jornadaNombre)->first()
        ?? Sys_Jornada::factory()->create(['nombre' => $jornadaNombre]);

    return EstablecimientoModalidadJornada::factory()->create([
        'establecimiento_modalidad_id' => $establecimientoModalidad->id,
        'jornada_id' => $catalogoJornada->id,
    ]);
}

/**
 * @return array<string, int>
 */
function activeOfertaSession(EstablecimientoModalidadJornada $oferta): array
{
    return [ActiveOferta::SESSION_KEY => $oferta->id];
}

function periodoActivoDe(EstablecimientoModalidadJornada $oferta, array $atributos = []): EstablecimientoPeriodo
{
    $oferta->loadMissing('establecimientoModalidad');

    return EstablecimientoPeriodo::factory()->activo()->create([
        'establecimiento_id' => $oferta->establecimientoModalidad->establecimiento_id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        ...$atributos,
    ]);
}

/**
 * @return array<string, int>
 */
function activePeriodoSession(EstablecimientoPeriodo $periodo): array
{
    return [ActivePeriodo::SESSION_KEY => $periodo->id];
}

/**
 * @return array<string, int>
 */
function activeContextSession(EstablecimientoModalidadJornada $oferta, EstablecimientoPeriodo $periodo): array
{
    return [
        ...activeOfertaSession($oferta),
        ...activePeriodoSession($periodo),
    ];
}
