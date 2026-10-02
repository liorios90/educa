<?php

use App\Enums\EsquemaCiclo;
use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

describe('edit', function () {
    it('allows administrators to configure the active period', function () {
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'Unidad Educativa Andina']);
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento, ['modalidad' => 'Presencial', 'jornada' => 'Matutina']);
        $periodo = periodoActivoDe($oferta, ['nombre' => '2026-2027 Sierra']);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->get(route('Admin.periodo'))
            ->assertOk()
            ->assertSee('Periodo')
            ->assertSee('Unidad Educativa Andina')
            ->assertSee('Presencial · Matutina')
            ->assertSee('2026-2027 Sierra')
            ->assertSee('Quimestres')
            ->assertSee('Trimestres')
            ->assertSee('Parciales por ciclo')
            ->assertSee('Primer quimestre')
            ->assertSee('Cierre del periodo')
            ->assertSee('Examen final')
            ->assertSee('Proyecto final')
            ->assertSee('primer ciclo 30%')
            ->assertSee('examen final 20%')
            ->assertSee('Guardar configuración');
    });

    it('explains when there is no active period', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->get(route('Admin.periodo'))
            ->assertOk()
            ->assertSee('No existe ningún periodo activo.')
            ->assertDontSee('Guardar configuración');
    });

    it('explains when the establishment has no ofertas', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->get(route('Admin.periodo'))
            ->assertOk()
            ->assertSee('Sistemas aún no ha asignado modalidades y jornadas a este establecimiento.')
            ->assertDontSee('Guardar configuración');
    });

    it('redirects administrators to choose an oferta when several exist and none is selected', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        ofertaDe($establecimiento, ['jornada' => 'Matutina']);
        ofertaDe($establecimiento, ['jornada' => 'Vespertina']);

        $this->actingAs($admin)
            ->get(route('Admin.periodo'))
            ->assertRedirect(route('oferta.select'));
    });

    it('escapes period names on the periodo screen', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta, ['nombre' => "<script>alert('xss')</script>"]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->get(route('Admin.periodo'))
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });

    it('forbids systems users from opening periodo configuration', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get(route('Admin.periodo'))
            ->assertForbidden();
    });

    it('redirects guests from periodo configuration to login', function () {
        $this->get(route('Admin.periodo'))
            ->assertRedirect(route('login'));
    });

    it('forbids administrators without an establishment', function () {
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.periodo'))
            ->assertForbidden();
    });

    it('shows the periodo option to administrators', function () {
        $this->seed(NavigationSeeder::class);
        $admin = adminOf(Establecimiento::factory()->create());

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertSee('Periodo')
            ->assertSee(route('Admin.periodo'), false);
    });
});

describe('update', function () {
    it('saves the evaluation scheme of the active period', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->put(route('Admin.periodo.update'), evaluacionQuimestral())
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHas('status', 'periodo-evaluacion-updated');

        $periodo->refresh();

        expect($periodo->esquema_ciclo)->toBe(EsquemaCiclo::Quimestres)
            ->and($periodo->numero_parciales)->toBe(2)
            ->and($periodo->porcentaje_examen_final)->toBe('0.00')
            ->and($periodo->porcentaje_proyecto_final)->toBe('0.00');

        $ciclos = $periodo->ciclos()->orderBy('orden')->get();

        expect($ciclos)->toHaveCount(2);
        expect($ciclos[0]->nombre)->toBe('Primer quimestre');
        expect($ciclos[0]->porcentaje)->toBe('50.00');
        expect($ciclos[0]->porcentaje_insumos)->toBe('70.00');
        expect($ciclos[0]->porcentaje_examen)->toBe('30.00');
        expect($ciclos[0]->porcentaje_proyecto)->toBe('0.00');
        expect($ciclos[1]->nombre)->toBe('Segundo quimestre');
        expect($ciclos[1]->porcentaje_insumos)->toBe('80.00');
        expect($ciclos[1]->porcentaje_examen)->toBe('0.00');
        expect($ciclos[1]->porcentaje_proyecto)->toBe('20.00');
    });

    it('replaces previous cycles of the same period', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta, [
            'esquema_ciclo' => EsquemaCiclo::Trimestres,
            'numero_parciales' => 3,
        ]);
        EstablecimientoPeriodoCiclo::factory()->create([
            'establecimiento_periodo_id' => $periodo->id,
            'orden' => 1,
            'nombre' => 'Viejo',
        ]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->put(route('Admin.periodo.update'), evaluacionQuimestral())
            ->assertRedirect(route('Admin.periodo'));

        expect($periodo->ciclos()->count())->toBe(2)
            ->and($periodo->fresh()->esquema_ciclo)->toBe(EsquemaCiclo::Quimestres)
            ->and($periodo->ciclos()->where('nombre', 'Viejo')->exists())->toBeFalse();
    });

    it('does not change the period of another oferta', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $otra = ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
        $periodoAjeno = periodoActivoDe($otra, ['nombre' => 'Ajeno']);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->put(route('Admin.periodo.update'), evaluacionQuimestral())
            ->assertRedirect(route('Admin.periodo'));

        expect($periodoAjeno->fresh()->esquema_ciclo)->toBeNull()
            ->and($periodoAjeno->ciclos()->count())->toBe(0);
    });

    it('rejects an empty evaluation payload', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.periodo'))
            ->put(route('Admin.periodo.update'), [])
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHasErrors([
                'esquema_ciclo',
                'numero_parciales',
                'ciclos',
            ]);
    });

    it('rejects cycle weights that do not add up to 100 percent', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $payload = evaluacionQuimestral();
        $payload['ciclos'][0]['porcentaje'] = '10';
        $payload['ciclos'][1]['porcentaje'] = '20';

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.periodo'))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHasErrors([
                'totales' => 'Los ciclos, el examen final y el proyecto final deben sumar 100%.',
            ]);
    });

    it('saves a period-level final exam and project with the cycles', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $payload = evaluacionQuimestral();
        $payload['ciclos'][0]['porcentaje'] = '30';
        $payload['ciclos'][1]['porcentaje'] = '30';
        $payload['tiene_examen_final'] = '1';
        $payload['porcentaje_examen_final'] = '20';
        $payload['tiene_proyecto_final'] = '1';
        $payload['porcentaje_proyecto_final'] = '20';

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'));

        $periodo->refresh();

        expect($periodo->porcentaje_examen_final)->toBe('20.00')
            ->and($periodo->porcentaje_proyecto_final)->toBe('20.00');

        $ciclos = $periodo->ciclos()->orderBy('orden')->get();

        expect($ciclos[0]->porcentaje)->toBe('30.00')
            ->and($ciclos[1]->porcentaje)->toBe('30.00');
    });

    it('rejects a quimestral scheme that does not have two cycles', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $payload = evaluacionQuimestral();
        unset($payload['ciclos'][1]);

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.periodo'))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHasErrors([
                'ciclos' => 'Los quimestres deben tener 2 ciclos.',
            ]);
    });

    it('rejects insumos, exam and project weights that do not add up to 100 percent of the cycle', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $payload = evaluacionQuimestral();
        $payload['ciclos'][0]['porcentaje_insumos'] = '10';
        $payload['ciclos'][0]['porcentaje_examen'] = '10';
        $payload['ciclos'][0]['porcentaje_proyecto'] = '10';

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->from(route('Admin.periodo'))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'))
            ->assertSessionHasErrors([
                'ciclos.0.porcentaje_insumos' => 'Los insumos, el examen y el proyecto del ciclo deben sumar 100%.',
            ]);
    });

    it('saves a cycle that has both an exam and a project', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);
        $periodo = periodoActivoDe($oferta);
        $payload = evaluacionQuimestral();
        $payload['ciclos'][0]['tiene_examen'] = '1';
        $payload['ciclos'][0]['porcentaje_examen'] = '20';
        $payload['ciclos'][0]['tiene_proyecto'] = '1';
        $payload['ciclos'][0]['porcentaje_proyecto'] = '10';
        $payload['ciclos'][0]['porcentaje_insumos'] = '70';

        $this->actingAs($admin)
            ->withSession(activeContextSession($oferta, $periodo))
            ->put(route('Admin.periodo.update'), $payload)
            ->assertRedirect(route('Admin.periodo'));

        $ciclo = $periodo->ciclos()->orderBy('orden')->first();

        expect($ciclo->porcentaje_insumos)->toBe('70.00')
            ->and($ciclo->porcentaje_examen)->toBe('20.00')
            ->and($ciclo->porcentaje_proyecto)->toBe('10.00');
    });

    it('forbids updating evaluation when there is no active period', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $oferta = ofertaDe($establecimiento);

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->put(route('Admin.periodo.update'), evaluacionQuimestral())
            ->assertForbidden();
    });
});

/**
 * @return array{
 *     esquema_ciclo: string,
 *     numero_parciales: int,
 *     tiene_examen_final: string,
 *     porcentaje_examen_final: string,
 *     tiene_proyecto_final: string,
 *     porcentaje_proyecto_final: string,
 *     ciclos: list<array{nombre: string, porcentaje: string, porcentaje_insumos: string, tiene_examen: string, porcentaje_examen: string, tiene_proyecto: string, porcentaje_proyecto: string}>
 * }
 */
function evaluacionQuimestral(): array
{
    return [
        'esquema_ciclo' => EsquemaCiclo::Quimestres->value,
        'numero_parciales' => 2,
        'tiene_examen_final' => '0',
        'porcentaje_examen_final' => '0',
        'tiene_proyecto_final' => '0',
        'porcentaje_proyecto_final' => '0',
        'ciclos' => [
            [
                'nombre' => 'Primer quimestre',
                'porcentaje' => '50',
                'porcentaje_insumos' => '70',
                'tiene_examen' => '1',
                'porcentaje_examen' => '30',
                'tiene_proyecto' => '0',
                'porcentaje_proyecto' => '0',
            ],
            [
                'nombre' => 'Segundo quimestre',
                'porcentaje' => '50',
                'porcentaje_insumos' => '80',
                'tiene_examen' => '0',
                'porcentaje_examen' => '0',
                'tiene_proyecto' => '1',
                'porcentaje_proyecto' => '20',
            ],
        ],
    ];
}
