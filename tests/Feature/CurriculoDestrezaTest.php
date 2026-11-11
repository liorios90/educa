<?php

use App\Enums\CalificacionDestreza;
use App\Enums\Role;
use App\Models\EstablecimientoIndicadorCalificacion;
use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Destreza;
use App\Models\Sys_Grado;
use App\Models\Sys_IndicadorEvaluacion;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use App\Models\User;

/**
 * @return array{user: User, nivel: Sys_Nivel, subnivel: Sys_Subnivel, area: Sys_Area, asignatura: Sys_Asignatura, grado: Sys_Grado}
 */
function escenarioCurriculoDestrezas(bool $porDestrezas = true): array
{
    $user = assignRole(User::factory()->create(), Role::Sistemas);
    $nivel = Sys_Nivel::factory()->create(['nombre' => 'Educación General Básica']);
    $subnivel = Sys_Subnivel::factory()->for($nivel, 'nivel')->create([
        'nombre' => 'Preparatoria',
        'califica_por_destrezas' => $porDestrezas,
    ]);
    $grado = Sys_Grado::factory()->for($subnivel, 'subnivel')->create(['nombre' => 'Primero de EGB']);
    $area = Sys_Area::factory()->for($subnivel, 'subnivel')->create(['nombre' => 'Lengua y Literatura']);
    $asignatura = Sys_Asignatura::factory()->for($area, 'area')->create(['nombre' => 'Lengua y Literatura']);

    return compact('user', 'nivel', 'subnivel', 'area', 'asignatura', 'grado');
}

it('redirects guests from the curriculum skills form', function () {
    $escenario = escenarioCurriculoDestrezas();

    $this->post(route('sistemas.curriculo.destrezas.store', [
        $escenario['nivel'],
        $escenario['subnivel'],
        $escenario['area'],
        $escenario['asignatura'],
    ]), [])->assertRedirect(route('login'));
});

it('forbids an administrator from creating a skill', function () {
    $escenario = escenarioCurriculoDestrezas();
    $admin = assignRole(User::factory()->create(), Role::Admin);

    $this->actingAs($admin)
        ->post(route('sistemas.curriculo.destrezas.store', [
            $escenario['nivel'],
            $escenario['subnivel'],
            $escenario['area'],
            $escenario['asignatura'],
        ]), [
            'grado_id' => $escenario['grado']->id,
            'codigo' => 'LL.1',
            'descripcion' => 'Comunicar datos de su identidad',
            'orden' => 1,
        ])
        ->assertForbidden();
});

it('stores a skill and its evaluation indicator', function () {
    $escenario = escenarioCurriculoDestrezas();

    $this->actingAs($escenario['user'])
        ->post(route('sistemas.curriculo.destrezas.store', [
            $escenario['nivel'],
            $escenario['subnivel'],
            $escenario['area'],
            $escenario['asignatura'],
        ]), [
            'grado_id' => $escenario['grado']->id,
            'codigo' => 'LL.1',
            'descripcion' => 'Comunicar datos de su identidad',
            'orden' => 1,
        ])
        ->assertRedirect(route('sistemas.curriculo', [
            'nivel' => $escenario['nivel']->id,
            'subnivel' => $escenario['subnivel']->id,
            'area' => $escenario['area']->id,
        ]))
        ->assertSessionHas('status', 'destreza-created');

    $destreza = Sys_Destreza::query()->firstOrFail();

    $this->actingAs($escenario['user'])
        ->post(route('sistemas.curriculo.indicadores.store', [
            $escenario['nivel'],
            $escenario['subnivel'],
            $escenario['area'],
            $escenario['asignatura'],
            $destreza,
        ]), [
            'codigo' => 'I.1',
            'descripcion' => 'Dice su nombre completo',
            'orden' => 1,
        ])
        ->assertSessionHas('status', 'indicador-created');

    expect(Sys_IndicadorEvaluacion::query()->where('destreza_id', $destreza->id)->value('descripcion'))
        ->toBe('Dice su nombre completo');
});

it('rejects a grade from another subnivel', function () {
    $escenario = escenarioCurriculoDestrezas();
    $otro = Sys_Grado::factory()->create(['nombre' => 'Décimo de EGB']);

    $this->actingAs($escenario['user'])
        ->from(route('sistemas.curriculo'))
        ->post(route('sistemas.curriculo.destrezas.store', [
            $escenario['nivel'],
            $escenario['subnivel'],
            $escenario['area'],
            $escenario['asignatura'],
        ]), [
            'grado_id' => $otro->id,
            'codigo' => 'LL.1',
            'descripcion' => 'Comunicar datos de su identidad',
            'orden' => 1,
        ])
        ->assertSessionHasErrors([
            'grado_id' => 'El grado no pertenece a este subnivel.',
        ], null, 'destreza-create-'.$escenario['asignatura']->id);
});

it('rejects skills when the subnivel is not evaluated by skills', function () {
    $escenario = escenarioCurriculoDestrezas(false);

    $this->actingAs($escenario['user'])
        ->from(route('sistemas.curriculo'))
        ->post(route('sistemas.curriculo.destrezas.store', [
            $escenario['nivel'],
            $escenario['subnivel'],
            $escenario['area'],
            $escenario['asignatura'],
        ]), [
            'grado_id' => $escenario['grado']->id,
            'codigo' => 'LL.1',
            'descripcion' => 'Comunicar datos de su identidad',
            'orden' => 1,
        ])
        ->assertSessionHasErrors([
            'descripcion' => 'Este subnivel no evalúa por destrezas.',
        ], null, 'destreza-create-'.$escenario['asignatura']->id);

    expect(Sys_Destreza::query()->count())->toBe(0);
});

it('refuses to delete a skill that already has grades', function () {
    $escenario = escenarioCurriculoDestrezas();
    $destreza = Sys_Destreza::factory()->create([
        'asignatura_id' => $escenario['asignatura']->id,
        'grado_id' => $escenario['grado']->id,
    ]);
    $indicador = Sys_IndicadorEvaluacion::factory()->create(['destreza_id' => $destreza->id]);
    EstablecimientoIndicadorCalificacion::factory()->create([
        'indicador_evaluacion_id' => $indicador->id,
        'calificacion' => CalificacionDestreza::Alcanzado,
    ]);

    $this->actingAs($escenario['user'])
        ->delete(route('sistemas.curriculo.destrezas.destroy', [
            $escenario['nivel'],
            $escenario['subnivel'],
            $escenario['area'],
            $escenario['asignatura'],
            $destreza,
        ]))
        ->assertRedirect()
        ->assertSessionHas('error', 'Esta destreza ya tiene calificaciones registradas y no se puede eliminar.');

    expect(Sys_Destreza::query()->whereKey($destreza->id)->exists())->toBeTrue();
});
