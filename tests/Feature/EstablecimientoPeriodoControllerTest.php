<?php

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\EstablecimientoPeriodo;
use App\Models\User;

describe('index', function () {
    it('shows a periodos link on the establecimientos list', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'UE Cotopaxi']);

        $this->actingAs($user)
            ->get(route('sistemas.establecimientos'))
            ->assertOk()
            ->assertSee('Periodos')
            ->assertSee(route('sistemas.establecimientos.periodos', $establecimiento), false);
    });

    it('allows systems users to open periodos of an establishment', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'UE Cotopaxi']);
        $oferta = ofertaDe($establecimiento, ['modalidad' => 'Presencial', 'jornada' => 'Matutina']);
        EstablecimientoPeriodo::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nombre' => '2026-2027',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2027-06-30',
            'activo' => true,
        ]);

        $this->actingAs($user)
            ->get(route('sistemas.establecimientos.periodos', $establecimiento))
            ->assertOk()
            ->assertSee('Periodos')
            ->assertSee('UE Cotopaxi')
            ->assertSee('Presencial · Matutina')
            ->assertSee('2026-2027')
            ->assertSee('01/09/2026')
            ->assertSee('30/06/2027')
            ->assertSee('Crear periodo')
            ->assertSee('Activo')
            ->assertDontSee('Intensivo');
    });

    it('lists periods of an oferta from newest to oldest', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        $oferta = ofertaDe($establecimiento, ['modalidad' => 'Presencial', 'jornada' => 'Matutina']);
        EstablecimientoPeriodo::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nombre' => '2025-2026',
            'activo' => false,
        ]);
        EstablecimientoPeriodo::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nombre' => '2026-2027',
            'activo' => true,
        ]);

        $this->actingAs($user)
            ->get(route('sistemas.establecimientos.periodos', $establecimiento))
            ->assertOk()
            ->assertSeeInOrder(['2026-2027', '2025-2026'])
            ->assertSee('Inactivo');
    });

    it('shows an empty state when the establishment has no ofertas', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();

        $this->actingAs($user)
            ->get(route('sistemas.establecimientos.periodos', $establecimiento))
            ->assertOk()
            ->assertSee('Este establecimiento no tiene modalidades y jornadas.')
            ->assertDontSee('Crear periodo');
    });

    it('escapes period names on the periodos screen', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        $oferta = ofertaDe($establecimiento);
        EstablecimientoPeriodo::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nombre' => "<script>alert('xss')</script>",
        ]);

        $this->actingAs($user)
            ->get(route('sistemas.establecimientos.periodos', $establecimiento))
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });

    it('forbids administrators from opening periodos', function () {
        $user = assignRole(User::factory()->create(), Role::Admin);
        $establecimiento = Establecimiento::factory()->create();

        $this->actingAs($user)
            ->get(route('sistemas.establecimientos.periodos', $establecimiento))
            ->assertForbidden();
    });

    it('redirects guests from periodos to login', function () {
        $establecimiento = Establecimiento::factory()->create();

        $this->get(route('sistemas.establecimientos.periodos', $establecimiento))
            ->assertRedirect(route('login'));
    });
});

describe('store', function () {
    it('creates a period for a modalidad and jornada of the establishment', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        $oferta = ofertaDe($establecimiento, ['modalidad' => 'Presencial', 'jornada' => 'Vespertina']);

        $this->actingAs($actor)
            ->post(route('sistemas.establecimientos.periodos.store', $establecimiento), [
                'establecimiento_modalidad_jornada_id' => $oferta->id,
                'nombre' => '2026-2027 Vespertina',
                'fecha_inicio' => '2026-01-05',
                'fecha_fin' => '2026-02-27',
                'activo' => '1',
            ])
            ->assertRedirect(route('sistemas.establecimientos.periodos', [
                'establecimiento' => $establecimiento,
                'oferta' => $oferta->id,
            ]))
            ->assertSessionHas('status', 'periodo-created');

        $periodo = EstablecimientoPeriodo::query()->where('nombre', '2026-2027 Vespertina')->firstOrFail();

        expect($periodo->establecimiento_id)->toBe($establecimiento->id);
        expect($periodo->establecimiento_modalidad_jornada_id)->toBe($oferta->id);
        expect($periodo->fecha_inicio->toDateString())->toBe('2026-01-05');
        expect($periodo->fecha_fin->toDateString())->toBe('2026-02-27');
        expect($periodo->activo)->toBeTrue();
    });

    it('deactivates the previous active period of the same oferta', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        $matutina = ofertaDe($establecimiento, ['modalidad' => 'Presencial', 'jornada' => 'Matutina']);
        $vespertina = ofertaDe($establecimiento, ['modalidad' => 'Presencial', 'jornada' => 'Vespertina']);
        $anterior = EstablecimientoPeriodo::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $matutina->id,
            'nombre' => '2025-2026',
            'activo' => true,
        ]);
        $otraOferta = EstablecimientoPeriodo::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $vespertina->id,
            'nombre' => '2026 Vespertina',
            'activo' => true,
        ]);

        $this->actingAs($actor)
            ->post(route('sistemas.establecimientos.periodos.store', $establecimiento), [
                'establecimiento_modalidad_jornada_id' => $matutina->id,
                'nombre' => '2026-2027',
                'fecha_inicio' => '2026-09-01',
                'fecha_fin' => '2027-06-30',
                'activo' => '1',
            ])
            ->assertRedirect(route('sistemas.establecimientos.periodos', [
                'establecimiento' => $establecimiento,
                'oferta' => $matutina->id,
            ]));

        expect($anterior->fresh()->activo)->toBeFalse();
        expect($otraOferta->fresh()->activo)->toBeTrue();
        expect(EstablecimientoPeriodo::query()->where('nombre', '2026-2027')->firstOrFail()->activo)->toBeTrue();
    });

    it('does not create a period for an oferta of another establishment', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        ofertaDe($establecimiento);
        $ajena = ofertaDe(Establecimiento::factory()->create());

        $this->actingAs($actor)
            ->from(route('sistemas.establecimientos.periodos', $establecimiento))
            ->post(route('sistemas.establecimientos.periodos.store', $establecimiento), [
                'establecimiento_modalidad_jornada_id' => $ajena->id,
                'nombre' => '2026-2027',
                'fecha_inicio' => '2026-09-01',
                'fecha_fin' => '2027-06-30',
            ])
            ->assertRedirect(route('sistemas.establecimientos.periodos', $establecimiento))
            ->assertSessionHasErrorsIn('periodo-create-'.$ajena->id, 'establecimiento_modalidad_jornada_id');

        $this->assertDatabaseMissing('establecimiento_periodos', [
            'establecimiento_id' => $establecimiento->id,
            'nombre' => '2026-2027',
        ]);
    });

    it('rejects an empty period payload', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        ofertaDe($establecimiento);

        $this->actingAs($actor)
            ->from(route('sistemas.establecimientos.periodos', $establecimiento))
            ->post(route('sistemas.establecimientos.periodos.store', $establecimiento), [])
            ->assertRedirect(route('sistemas.establecimientos.periodos', $establecimiento))
            ->assertSessionHasErrorsIn('periodo-create-new', [
                'establecimiento_modalidad_jornada_id',
                'nombre',
                'fecha_inicio',
                'fecha_fin',
            ]);
    });

    it('rejects a period whose end date is before the start date', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        $oferta = ofertaDe($establecimiento);

        $this->actingAs($actor)
            ->from(route('sistemas.establecimientos.periodos', $establecimiento))
            ->post(route('sistemas.establecimientos.periodos.store', $establecimiento), [
                'establecimiento_modalidad_jornada_id' => $oferta->id,
                'nombre' => '2026-2027',
                'fecha_inicio' => '2026-09-01',
                'fecha_fin' => '2026-08-01',
            ])
            ->assertRedirect(route('sistemas.establecimientos.periodos', $establecimiento))
            ->assertSessionHasErrorsIn('periodo-create-'.$oferta->id, [
                'fecha_fin' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            ]);
    });
});

describe('update', function () {
    it('updates a period of the establishment', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        $oferta = ofertaDe($establecimiento);
        $periodo = EstablecimientoPeriodo::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nombre' => '2025-2026',
        ]);

        $this->actingAs($actor)
            ->patch(route('sistemas.establecimientos.periodos.update', [$establecimiento, $periodo]), [
                'establecimiento_modalidad_jornada_id' => $oferta->id,
                'nombre' => '2026-2027 Sierra',
                'fecha_inicio' => '2026-09-02',
                'fecha_fin' => '2027-07-01',
                'activo' => '1',
            ])
            ->assertRedirect(route('sistemas.establecimientos.periodos', [
                'establecimiento' => $establecimiento,
                'oferta' => $oferta->id,
            ]))
            ->assertSessionHas('status', 'periodo-updated');

        $actualizado = $periodo->fresh();

        expect($actualizado->nombre)->toBe('2026-2027 Sierra');
        expect($actualizado->fecha_inicio->toDateString())->toBe('2026-09-02');
        expect($actualizado->fecha_fin->toDateString())->toBe('2027-07-01');
        expect($actualizado->activo)->toBeTrue();
    });

    it('deactivates the previous active period when another of the same oferta is activated', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        $oferta = ofertaDe($establecimiento);
        $anterior = EstablecimientoPeriodo::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nombre' => '2025-2026',
            'activo' => true,
        ]);
        $periodo = EstablecimientoPeriodo::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'nombre' => '2026-2027',
            'activo' => false,
        ]);

        $this->actingAs($actor)
            ->patch(route('sistemas.establecimientos.periodos.update', [$establecimiento, $periodo]), [
                'establecimiento_modalidad_jornada_id' => $oferta->id,
                'nombre' => '2026-2027',
                'fecha_inicio' => '2026-09-01',
                'fecha_fin' => '2027-06-30',
                'activo' => '1',
            ])
            ->assertRedirect(route('sistemas.establecimientos.periodos', [
                'establecimiento' => $establecimiento,
                'oferta' => $oferta->id,
            ]));

        expect($periodo->fresh()->activo)->toBeTrue();
        expect($anterior->fresh()->activo)->toBeFalse();
    });

    it('returns 404 when updating a period of another establishment', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        ofertaDe($establecimiento);
        $ajeno = Establecimiento::factory()->create();
        $periodoAjeno = EstablecimientoPeriodo::factory()->create([
            'establecimiento_id' => $ajeno->id,
            'establecimiento_modalidad_jornada_id' => ofertaDe($ajeno)->id,
            'nombre' => 'Ajeno',
        ]);

        $this->actingAs($actor)
            ->patch(route('sistemas.establecimientos.periodos.update', [$establecimiento, $periodoAjeno]), [
                'establecimiento_modalidad_jornada_id' => $periodoAjeno->establecimiento_modalidad_jornada_id,
                'nombre' => 'No debe cambiar',
                'fecha_inicio' => '2026-09-01',
                'fecha_fin' => '2027-06-30',
            ])
            ->assertNotFound();

        expect($periodoAjeno->fresh()->nombre)->toBe('Ajeno');
    });
});

describe('destroy', function () {
    it('deletes a period of the establishment', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        $periodo = EstablecimientoPeriodo::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => ofertaDe($establecimiento)->id,
        ]);

        $this->actingAs($actor)
            ->delete(route('sistemas.establecimientos.periodos.destroy', [$establecimiento, $periodo]))
            ->assertRedirect(route('sistemas.establecimientos.periodos', [
                'establecimiento' => $establecimiento,
                'oferta' => $periodo->establecimiento_modalidad_jornada_id,
            ]))
            ->assertSessionHas('status', 'periodo-deleted');

        $this->assertModelMissing($periodo);
    });

    it('returns 404 when deleting a period of another establishment', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        ofertaDe($establecimiento);
        $ajeno = Establecimiento::factory()->create();
        $periodoAjeno = EstablecimientoPeriodo::factory()->create([
            'establecimiento_id' => $ajeno->id,
            'establecimiento_modalidad_jornada_id' => ofertaDe($ajeno)->id,
        ]);

        $this->actingAs($actor)
            ->delete(route('sistemas.establecimientos.periodos.destroy', [$establecimiento, $periodoAjeno]))
            ->assertNotFound();

        $this->assertModelExists($periodoAjeno);
    });
});
