<?php

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Auth\ActiveRole;
use App\Auth\AuthContext;
use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\EstablecimientoPeriodo;
use App\Models\User;
use Database\Seeders\NavigationSeeder;

it('sends an administrator to choose modalidad and jornada after login', function () {
    $establecimiento = Establecimiento::factory()->create(['nombre' => 'UE Los Andes']);
    $admin = adminOf($establecimiento);
    $matutina = ofertaDe($establecimiento);
    ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
    $periodo = periodoActivoDe($matutina, [
        'nombre' => '2026-2027',
        'fecha_inicio' => '2026-05-01',
        'fecha_fin' => '2027-02-28',
    ]);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('oferta.select'));

    $this->assertAuthenticated();
    expect(session(ActiveOferta::SESSION_KEY))->toBeNull();

    $this->get(route('oferta.select'))
        ->assertOk()
        ->assertSee('¿En qué modalidad y jornada quieres entrar?')
        ->assertSee('UE Los Andes')
        ->assertSee('Modalidad')
        ->assertSee('Jornada')
        ->assertSee('Presencial')
        ->assertSee('Matutina')
        ->assertSee('Vespertina');

    $this->post(route('oferta.store'), [
        'establecimiento_modalidad_id' => $matutina->establecimiento_modalidad_id,
        'oferta' => $matutina->id,
    ])
        ->assertRedirect(route('dashboard', absolute: false));

    expect(session(ActiveOferta::SESSION_KEY))->toBe($matutina->id)
        ->and(session(ActivePeriodo::SESSION_KEY))->toBe($periodo->id)
        ->and(session(AuthContext::PERIODO_KEY))->toMatchArray([
            'id' => $periodo->id,
            'nombre' => '2026-2027',
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2027-02-28',
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $matutina->id,
        ]);
});

it('redirects an administrator from the dashboard until modalidad and jornada are chosen', function () {
    $this->seed(NavigationSeeder::class);
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $matutina = ofertaDe($establecimiento);
    ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
    periodoActivoDe($matutina, ['nombre' => '2026-2027']);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertRedirect(route('oferta.select'));

    $this->actingAs($admin)
        ->from(route('oferta.select'))
        ->post(route('oferta.store'), [
            'establecimiento_modalidad_id' => $matutina->establecimiento_modalidad_id,
            'oferta' => $matutina->id,
        ])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Presencial · Matutina')
        ->assertSee('2026-2027');
});

it('does not list another establishment oferta on the selector', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    ofertaDe($establecimiento);
    ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
    ofertaDe(Establecimiento::factory()->create(), [
        'modalidad' => 'Semipresencial',
        'jornada' => 'Nocturna ajena',
    ]);

    $this->actingAs($admin)
        ->get(route('oferta.select'))
        ->assertOk()
        ->assertSee('Presencial')
        ->assertDontSee('Semipresencial')
        ->assertDontSee('Nocturna ajena');
});

it('rejects an oferta that does not belong to the administrator establishment', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $propia = ofertaDe($establecimiento);
    ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
    $ajena = ofertaDe(Establecimiento::factory()->create(), [
        'modalidad' => 'Virtual',
        'jornada' => 'Nocturna',
    ]);

    $this->actingAs($admin)
        ->from(route('oferta.select'))
        ->post(route('oferta.store'), [
            'establecimiento_modalidad_id' => $propia->establecimiento_modalidad_id,
            'oferta' => $ajena->id,
        ])
        ->assertRedirect(route('oferta.select'))
        ->assertSessionHasErrors('oferta');

    expect(session(ActiveOferta::SESSION_KEY))->toBeNull();
});

it('rejects a jornada that does not belong to the selected modalidad', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $presencial = ofertaDe($establecimiento, ['modalidad' => 'Presencial', 'jornada' => 'Matutina']);
    $virtual = ofertaDe($establecimiento, ['modalidad' => 'Virtual', 'jornada' => 'Nocturna']);

    $this->actingAs($admin)
        ->from(route('oferta.select'))
        ->post(route('oferta.store'), [
            'establecimiento_modalidad_id' => $presencial->establecimiento_modalidad_id,
            'oferta' => $virtual->id,
        ])
        ->assertRedirect(route('oferta.select'))
        ->assertSessionHasErrors('oferta');
});

it('rejects an empty modalidad and jornada selection', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    ofertaDe($establecimiento);
    ofertaDe($establecimiento, ['jornada' => 'Vespertina']);

    $this->actingAs($admin)
        ->from(route('oferta.select'))
        ->post(route('oferta.store'), [])
        ->assertRedirect(route('oferta.select'))
        ->assertSessionHasErrors(['establecimiento_modalidad_id', 'oferta']);
});

it('sends an administrator to choose the only configured oferta after login', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $oferta = ofertaDe($establecimiento);
    $periodo = periodoActivoDe($oferta);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('oferta.select'));

    expect(session(ActiveOferta::SESSION_KEY))->toBeNull();

    $this->get(route('oferta.select'))
        ->assertOk()
        ->assertSee('Presencial')
        ->assertSee('Matutina');

    $this->post(route('oferta.store'), [
        'establecimiento_modalidad_id' => $oferta->establecimiento_modalidad_id,
        'oferta' => $oferta->id,
    ])
        ->assertRedirect(route('dashboard', absolute: false));

    expect(session(ActiveOferta::SESSION_KEY))->toBe($oferta->id)
        ->and(session(ActivePeriodo::SESSION_KEY))->toBe($periodo->id);
});

it('does not ask for an oferta when the establishment has none configured', function () {
    $admin = adminOf(Establecimiento::factory()->create());

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('dashboard', absolute: false));

    expect(session(ActiveOferta::SESSION_KEY))->toBeNull();
});

it('does not ask systems users to choose an oferta', function () {
    $establecimiento = Establecimiento::factory()->create();
    $user = assignRole(User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Sistemas);
    ofertaDe($establecimiento);
    ofertaDe($establecimiento, ['jornada' => 'Vespertina']);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('dashboard', absolute: false));

    expect(session(ActiveOferta::SESSION_KEY))->toBeNull();
});

it('asks for an oferta after a multi-role user chooses administrator', function () {
    $establecimiento = Establecimiento::factory()->create();
    $user = User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
    ]);
    assignRole($user, Role::Admin);
    assignRole($user, Role::Sistemas);
    $matutina = ofertaDe($establecimiento);
    ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
    periodoActivoDe($matutina);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('role.select'));

    $this->post(route('role.store'), ['role' => Role::Admin->value])
        ->assertRedirect(route('oferta.select'));

    expect(session(ActiveRole::SESSION_KEY))->toBe(Role::Admin->value);
    expect(session(ActiveOferta::SESSION_KEY))->toBeNull();

    $this->post(route('oferta.store'), [
        'establecimiento_modalidad_id' => $matutina->establecimiento_modalidad_id,
        'oferta' => $matutina->id,
    ])
        ->assertRedirect(route('dashboard', absolute: false));
});

it('does not ask for an oferta when a multi-role user chooses systems', function () {
    $establecimiento = Establecimiento::factory()->create();
    $user = User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
    ]);
    assignRole($user, Role::Admin);
    assignRole($user, Role::Sistemas);
    ofertaDe($establecimiento);
    ofertaDe($establecimiento, ['jornada' => 'Vespertina']);

    $this->actingAs($user)
        ->post(route('role.store'), ['role' => Role::Sistemas->value])
        ->assertRedirect(route('dashboard', absolute: false));

    expect(session(ActiveOferta::SESSION_KEY))->toBeNull();
});

it('returns the administrator to the intended page after choosing an oferta', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $matutina = ofertaDe($establecimiento);
    ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
    periodoActivoDe($matutina);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertRedirect(route('oferta.select'));

    $this->actingAs($admin)
        ->post(route('oferta.store'), [
            'establecimiento_modalidad_id' => $matutina->establecimiento_modalidad_id,
            'oferta' => $matutina->id,
        ])
        ->assertRedirect(route('dashboard', absolute: false));
});

it('redirects guests from the oferta selector to login', function () {
    $this->get(route('oferta.select'))
        ->assertRedirect(route('login'));
});

it('forbids systems users from storing an oferta', function () {
    $user = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($user)
        ->post(route('oferta.store'), [
            'establecimiento_modalidad_id' => 1,
            'oferta' => 1,
        ])
        ->assertForbidden();
});

it('escapes modalidad and jornada names on the selector', function () {
    $establecimiento = Establecimiento::factory()->create(['nombre' => "<script>alert('xss')</script>"]);
    $admin = adminOf($establecimiento);
    ofertaDe($establecimiento, [
        'modalidad' => "<script>alert('modalidad')</script>",
        'jornada' => "<script>alert('jornada')</script>",
    ]);
    ofertaDe($establecimiento, ['jornada' => 'Vespertina']);

    $this->actingAs($admin)
        ->get(route('oferta.select'))
        ->assertSee("<script>alert('xss')</script>")
        ->assertDontSee("<script>alert('xss')</script>", false)
        ->assertSee("<script>alert('modalidad')</script>")
        ->assertDontSee("<script>alert('modalidad')</script>", false)
        ->assertSee("<script>alert('jornada')</script>")
        ->assertDontSee("<script>alert('jornada')</script>", false);
});

it('does not let an administrator enter when the oferta has no active period', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $matutina = ofertaDe($establecimiento);
    ofertaDe($establecimiento, ['jornada' => 'Vespertina']);

    $this->actingAs($admin)
        ->get(route('oferta.select'))
        ->assertOk()
        ->assertSee('No existe ningún periodo activo.');

    $this->actingAs($admin)
        ->from(route('oferta.select'))
        ->post(route('oferta.store'), [
            'establecimiento_modalidad_id' => $matutina->establecimiento_modalidad_id,
            'oferta' => $matutina->id,
        ])
        ->assertRedirect(route('oferta.select'))
        ->assertSessionHasErrors(['periodo' => 'No existe ningún periodo activo.']);

    expect(session(ActiveOferta::SESSION_KEY))->toBeNull()
        ->and(session(ActivePeriodo::SESSION_KEY))->toBeNull()
        ->and(session(AuthContext::PERIODO_KEY))->toBeNull();
});

it('does not treat an inactive period as the active period of the oferta', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $matutina = ofertaDe($establecimiento);
    ofertaDe($establecimiento, ['jornada' => 'Vespertina']);

    EstablecimientoPeriodo::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $matutina->id,
        'nombre' => '2025-2026',
        'activo' => false,
    ]);

    $this->actingAs($admin)
        ->from(route('oferta.select'))
        ->post(route('oferta.store'), [
            'establecimiento_modalidad_id' => $matutina->establecimiento_modalidad_id,
            'oferta' => $matutina->id,
        ])
        ->assertRedirect(route('oferta.select'))
        ->assertSessionHasErrors(['periodo' => 'No existe ningún periodo activo.']);

    expect(session(ActivePeriodo::SESSION_KEY))->toBeNull();
});

it('does not use the active period of another jornada in the same establishment', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $matutina = ofertaDe($establecimiento);
    $vespertina = ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
    periodoActivoDe($vespertina, ['nombre' => '2026 Vespertina']);

    $this->actingAs($admin)
        ->from(route('oferta.select'))
        ->post(route('oferta.store'), [
            'establecimiento_modalidad_id' => $matutina->establecimiento_modalidad_id,
            'oferta' => $matutina->id,
        ])
        ->assertRedirect(route('oferta.select'))
        ->assertSessionHasErrors(['periodo' => 'No existe ningún periodo activo.']);

    expect(session(ActiveOferta::SESSION_KEY))->toBeNull()
        ->and(session(ActivePeriodo::SESSION_KEY))->toBeNull();
});

it('does not use the active period of another establishment', function () {
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $matutina = ofertaDe($establecimiento);
    ofertaDe($establecimiento, ['jornada' => 'Vespertina']);

    $ajena = ofertaDe(Establecimiento::factory()->create());
    periodoActivoDe($ajena, ['nombre' => 'Periodo ajeno']);

    $this->actingAs($admin)
        ->from(route('oferta.select'))
        ->post(route('oferta.store'), [
            'establecimiento_modalidad_id' => $matutina->establecimiento_modalidad_id,
            'oferta' => $matutina->id,
        ])
        ->assertRedirect(route('oferta.select'))
        ->assertSessionHasErrors(['periodo' => 'No existe ningún periodo activo.']);
});

it('sends the administrator back to the selector when the active period is deactivated', function () {
    $this->seed(NavigationSeeder::class);
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $matutina = ofertaDe($establecimiento);
    ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
    $periodo = periodoActivoDe($matutina, ['nombre' => '2026-2027']);

    $this->actingAs($admin)
        ->withSession([
            ...activeOfertaSession($matutina),
            ...activePeriodoSession($periodo),
        ])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('2026-2027');

    $periodo->update(['activo' => false]);

    $this->actingAs($admin)
        ->withSession([
            ...activeOfertaSession($matutina),
            ...activePeriodoSession($periodo),
        ])
        ->get(route('dashboard'))
        ->assertRedirect(route('oferta.select'))
        ->assertSessionHasErrors(['periodo' => 'No existe ningún periodo activo.']);
});

it('escapes the active period name on the dashboard', function () {
    $this->seed(NavigationSeeder::class);
    $establecimiento = Establecimiento::factory()->create();
    $admin = adminOf($establecimiento);
    $matutina = ofertaDe($establecimiento);
    ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
    $periodo = periodoActivoDe($matutina, ['nombre' => "<script>alert('periodo')</script>"]);

    $this->actingAs($admin)
        ->withSession([
            ...activeOfertaSession($matutina),
            ...activePeriodoSession($periodo),
        ])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee("<script>alert('periodo')</script>")
        ->assertDontSee("<script>alert('periodo')</script>", false);
});
