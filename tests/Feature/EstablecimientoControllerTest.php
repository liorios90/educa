<?php

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\EstablecimientoModalidad;
use App\Models\Sys_Circuito;
use App\Models\Sys_Distrito;
use App\Models\Sys_Jornada;
use App\Models\Sys_Modalidad;
use App\Models\Sys_Zona;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * @return array<string, mixed>
 */
function establecimientoPayload(Sys_Zona $zona, Sys_Distrito $distrito, Sys_Circuito $circuito, array $overrides = []): array
{
    $payload = [
        'nombre' => 'UE Los Andes',
        'descripcion' => 'Establecimiento de prueba',
        'direccion' => 'Av. Principal 123',
        'telefono' => '022222222',
        'representante' => 'Ana Perez',
        'codigo_amie' => '17H00001',
        'regimen' => 'Sierra',
        'email' => 'andes@example.com',
        'usuario' => 'sistemas',
        'activo' => '1',
        'logo' => UploadedFile::fake()->image('logo.png'),
        'zona_id' => $zona->id,
        'distrito_id' => $distrito->id,
        'circuito_id' => $circuito->id,
        'admin_name' => 'Director Andino',
        'admin_email' => 'director.andes@example.com',
        'admin_password' => 'password',
        'admin_password_confirmation' => 'password',
    ];

    if (! array_key_exists('modalidades', $overrides) && ! array_key_exists('jornadas', $overrides)) {
        $modalidad = Sys_Modalidad::factory()->create();
        $jornada = Sys_Jornada::factory()->create();
        $payload['modalidades'] = [$modalidad->id];
        $payload['jornadas'] = [$modalidad->id => [$jornada->id]];
    }

    return [
        ...$payload,
        ...$overrides,
    ];
}

describe('index', function () {
    it('allows systems users to open establecimientos', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'UE Cotopaxi']);

        $this->actingAs($user)
            ->get(route('sistemas.establecimientos'))
            ->assertOk()
            ->assertSee('Establecimientos')
            ->assertSee('UE Cotopaxi')
            ->assertSee($establecimiento->zona->nombre)
            ->assertSee($establecimiento->distrito->nombre)
            ->assertSee($establecimiento->circuito->nombre);
    });

    it('forbids administrators from opening establecimientos', function () {
        $user = assignRole(User::factory()->create(), Role::Admin);

        $this->actingAs($user)
            ->get(route('sistemas.establecimientos'))
            ->assertForbidden();
    });

    it('redirects guests from establecimientos to login', function () {
        $this->get(route('sistemas.establecimientos'))
            ->assertRedirect(route('login'));
    });

    it('escapes field values in the establecimientos list', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        Establecimiento::factory()->create([
            'nombre' => "<script>alert('xss')</script>",
        ]);

        $this->actingAs($user)
            ->get(route('sistemas.establecimientos'))
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });
});

describe('create', function () {
    it('shows zona distrito and circuito names in the selects', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $zona = Sys_Zona::factory()->create(['nombre' => 'Zona 8']);
        $distrito = Sys_Distrito::factory()->create([
            'nombre' => '09D01',
            'zona_id' => $zona->id,
        ]);
        Sys_Circuito::factory()->create([
            'nombre' => '09D01C01',
            'distrito_id' => $distrito->id,
        ]);

        $this->actingAs($user)
            ->get(route('sistemas.establecimientos.create'))
            ->assertOk()
            ->assertSee('Zona 8')
            ->assertSee('value="'.$zona->id.'"', false)
            ->assertSee('09D01')
            ->assertSee('09D01C01');
    });

    it('shows costa and sierra as regimen options and hides unused fields', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get(route('sistemas.establecimientos.create'))
            ->assertOk()
            ->assertSee('Costa')
            ->assertSee('Sierra')
            ->assertSee('type="file"', false)
            ->assertDontSee('Solo visible')
            ->assertDontSee('Misión')
            ->assertDontSee('Visión')
            ->assertDontSee('Ideario');
    });

    it('shows administrator fields on the create form', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get(route('sistemas.establecimientos.create'))
            ->assertOk()
            ->assertSee('Usuario administrador')
            ->assertSee('Nombre del administrador')
            ->assertSee('Correo del administrador');
    });

    it('shows modalidades and jornadas from the national catalog', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        $modalidad = Sys_Modalidad::factory()->create(['nombre' => 'Presencial']);
        Sys_Jornada::factory()->create(['nombre' => 'Matutina']);

        $this->actingAs($user)
            ->get(route('sistemas.establecimientos.create'))
            ->assertOk()
            ->assertSee('Modalidades y jornadas')
            ->assertSee('Presencial')
            ->assertSee('Matutina')
            ->assertSee('name="modalidades[]"', false)
            ->assertSee('name="jornadas['.$modalidad->id.'][]"', false);
    });

    it('escapes modalidad names on the create form', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);
        Sys_Modalidad::factory()->create(['nombre' => "<script>alert('xss')</script>"]);

        $this->actingAs($user)
            ->get(route('sistemas.establecimientos.create'))
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });
});

describe('store', function () {
    it('creates an establecimiento with catalog ids not names', function () {
        Storage::fake('public');
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $zona = Sys_Zona::factory()->create();
        $distrito = Sys_Distrito::factory()->create(['zona_id' => $zona->id]);
        $circuito = Sys_Circuito::factory()->create(['distrito_id' => $distrito->id]);

        $this->actingAs($actor)
            ->post(route('sistemas.establecimientos.store'), establecimientoPayload($zona, $distrito, $circuito))
            ->assertRedirect(route('sistemas.establecimientos'))
            ->assertSessionHas('status', 'establecimiento-created');

        $establecimiento = Establecimiento::query()->where('email', 'andes@example.com')->firstOrFail();

        expect($establecimiento->zona_id)->toBe($zona->id)
            ->and($establecimiento->distrito_id)->toBe($distrito->id)
            ->and($establecimiento->circuito_id)->toBe($circuito->id)
            ->and($establecimiento->regimen)->toBe('Sierra');

        $administrador = $establecimiento->administrador();

        expect($administrador)->not->toBeNull()
            ->and($administrador->name)->toBe('Director Andino')
            ->and($administrador->email)->toBe('director.andes@example.com')
            ->and($administrador->establecimiento_id)->toBe($establecimiento->id)
            ->and($administrador->hasRole(Role::Admin))->toBeTrue();

        expect(Hash::check('password', $administrador->password))->toBeTrue();

        Storage::disk('public')->assertExists($establecimiento->logo);
    });

    it('creates an establecimiento with modalidades and jornadas per modalidad', function () {
        Storage::fake('public');
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $zona = Sys_Zona::factory()->create();
        $distrito = Sys_Distrito::factory()->create(['zona_id' => $zona->id]);
        $circuito = Sys_Circuito::factory()->create(['distrito_id' => $distrito->id]);
        $presencial = Sys_Modalidad::factory()->create(['nombre' => 'Presencial']);
        $virtual = Sys_Modalidad::factory()->create(['nombre' => 'Virtual']);
        $matutina = Sys_Jornada::factory()->create(['nombre' => 'Matutina']);
        $vespertina = Sys_Jornada::factory()->create(['nombre' => 'Vespertina']);
        $otro = Sys_Jornada::factory()->create(['nombre' => 'Otro']);

        $this->actingAs($actor)
            ->post(route('sistemas.establecimientos.store'), establecimientoPayload($zona, $distrito, $circuito, [
                'modalidades' => [$presencial->id, $virtual->id],
                'jornadas' => [
                    $presencial->id => [$matutina->id, $vespertina->id],
                    $virtual->id => [$otro->id],
                ],
            ]))
            ->assertRedirect(route('sistemas.establecimientos'))
            ->assertSessionHasNoErrors();

        $establecimiento = Establecimiento::query()->where('email', 'andes@example.com')->firstOrFail();
        $ofertas = $establecimiento->establecimientoModalidades()->with('jornadas')->get()->keyBy('modalidad_id');

        expect($ofertas->keys()->sort()->values()->all())->toBe([$presencial->id, $virtual->id])
            ->and($ofertas[$presencial->id]->jornadas->pluck('id')->sort()->values()->all())->toBe([$matutina->id, $vespertina->id])
            ->and($ofertas[$virtual->id]->jornadas->pluck('id')->all())->toBe([$otro->id]);
    });

    it('ignores jornadas of modalidades that were not selected', function () {
        Storage::fake('public');
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $zona = Sys_Zona::factory()->create();
        $distrito = Sys_Distrito::factory()->create(['zona_id' => $zona->id]);
        $circuito = Sys_Circuito::factory()->create(['distrito_id' => $distrito->id]);
        $presencial = Sys_Modalidad::factory()->create();
        $semipresencial = Sys_Modalidad::factory()->create();
        $matutina = Sys_Jornada::factory()->create();
        $nocturna = Sys_Jornada::factory()->create();

        $this->actingAs($actor)
            ->post(route('sistemas.establecimientos.store'), establecimientoPayload($zona, $distrito, $circuito, [
                'modalidades' => [$presencial->id],
                'jornadas' => [
                    $presencial->id => [$matutina->id],
                    $semipresencial->id => [$nocturna->id],
                ],
            ]))
            ->assertRedirect(route('sistemas.establecimientos'));

        $establecimiento = Establecimiento::query()->where('email', 'andes@example.com')->firstOrFail();

        expect($establecimiento->establecimientoModalidades()->pluck('modalidad_id')->all())->toBe([$presencial->id]);
        $this->assertDatabaseMissing('establecimiento_modalidad_jornadas', [
            'jornada_id' => $nocturna->id,
        ]);
    });

    it('does not create the establecimiento when the administrator email is already taken', function () {
        Storage::fake('public');
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        User::factory()->create(['email' => 'director.andes@example.com']);
        $zona = Sys_Zona::factory()->create();
        $distrito = Sys_Distrito::factory()->create(['zona_id' => $zona->id]);
        $circuito = Sys_Circuito::factory()->create(['distrito_id' => $distrito->id]);

        $this->actingAs($actor)
            ->from(route('sistemas.establecimientos.create'))
            ->post(route('sistemas.establecimientos.store'), establecimientoPayload($zona, $distrito, $circuito))
            ->assertRedirect(route('sistemas.establecimientos.create'))
            ->assertSessionHasErrors('admin_email');

        $this->assertDatabaseMissing('establecimientos', ['email' => 'andes@example.com']);
    });

    it('rejects an empty payload', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->from(route('sistemas.establecimientos.create'))
            ->post(route('sistemas.establecimientos.store'), [])
            ->assertRedirect(route('sistemas.establecimientos.create'))
            ->assertSessionHasErrors(['nombre', 'zona_id', 'distrito_id', 'circuito_id', 'regimen', 'logo', 'admin_name', 'admin_email', 'admin_password', 'modalidades', 'jornadas']);
    });

    it('rejects a distrito that does not belong to the zona', function () {
        Storage::fake('public');
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $zona = Sys_Zona::factory()->create();
        $otraZona = Sys_Zona::factory()->create();
        $distrito = Sys_Distrito::factory()->create(['zona_id' => $otraZona->id]);
        $circuito = Sys_Circuito::factory()->create(['distrito_id' => $distrito->id]);

        $this->actingAs($actor)
            ->from(route('sistemas.establecimientos.create'))
            ->post(route('sistemas.establecimientos.store'), establecimientoPayload($zona, $distrito, $circuito))
            ->assertRedirect(route('sistemas.establecimientos.create'))
            ->assertSessionHasErrors('distrito_id');

        $this->assertDatabaseMissing('establecimientos', ['email' => 'andes@example.com']);
    });

    it('rejects a regimen that is not costa or sierra', function () {
        Storage::fake('public');
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $zona = Sys_Zona::factory()->create();
        $distrito = Sys_Distrito::factory()->create(['zona_id' => $zona->id]);
        $circuito = Sys_Circuito::factory()->create(['distrito_id' => $distrito->id]);

        $this->actingAs($actor)
            ->from(route('sistemas.establecimientos.create'))
            ->post(route('sistemas.establecimientos.store'), establecimientoPayload($zona, $distrito, $circuito, [
                'regimen' => 'Amazonia',
            ]))
            ->assertRedirect(route('sistemas.establecimientos.create'))
            ->assertSessionHasErrors('regimen');

        $this->assertDatabaseMissing('establecimientos', ['email' => 'andes@example.com']);
    });

    it('rejects a modalidad without a jornada', function () {
        Storage::fake('public');
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $zona = Sys_Zona::factory()->create();
        $distrito = Sys_Distrito::factory()->create(['zona_id' => $zona->id]);
        $circuito = Sys_Circuito::factory()->create(['distrito_id' => $distrito->id]);
        $modalidad = Sys_Modalidad::factory()->create();

        $this->actingAs($actor)
            ->from(route('sistemas.establecimientos.create'))
            ->post(route('sistemas.establecimientos.store'), establecimientoPayload($zona, $distrito, $circuito, [
                'modalidades' => [$modalidad->id],
                'jornadas' => [
                    $modalidad->id => [],
                ],
            ]))
            ->assertRedirect(route('sistemas.establecimientos.create'))
            ->assertSessionHasErrors([
                'jornadas.'.$modalidad->id => 'Selecciona al menos una jornada para cada modalidad marcada.',
            ]);

        $this->assertDatabaseMissing('establecimientos', ['email' => 'andes@example.com']);
    });
});

describe('update', function () {
    it('updates an establecimiento', function () {
        Storage::fake('public');
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create(['nombre' => 'Viejo']);
        $zona = $establecimiento->zona;
        $distrito = $establecimiento->distrito;
        $circuito = $establecimiento->circuito;

        $this->actingAs($actor)
            ->patch(
                route('sistemas.establecimientos.update', $establecimiento),
                establecimientoPayload($zona, $distrito, $circuito, [
                    'nombre' => 'UE Nueva',
                    'codigo_amie' => $establecimiento->codigo_amie,
                    'email' => $establecimiento->email,
                ]),
            )
            ->assertRedirect(route('sistemas.establecimientos'))
            ->assertSessionHas('status', 'establecimiento-updated');

        expect($establecimiento->fresh()->nombre)->toBe('UE Nueva');
    });

    it('prefills selected modalidades and jornadas on the edit form', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        $modalidad = Sys_Modalidad::factory()->create(['nombre' => 'Semipresencial']);
        $jornada = Sys_Jornada::factory()->create(['nombre' => 'Nocturna']);
        $oferta = EstablecimientoModalidad::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'modalidad_id' => $modalidad->id,
        ]);
        $oferta->jornadas()->attach($jornada->id);

        $this->actingAs($actor)
            ->get(route('sistemas.establecimientos.edit', $establecimiento))
            ->assertOk()
            ->assertSee('Semipresencial')
            ->assertSee('Nocturna')
            ->assertViewHas('selectedModalidadIds', [(string) $modalidad->id])
            ->assertViewHas('selectedJornadasPorModalidad', [
                (string) $modalidad->id => [(string) $jornada->id],
            ]);
    });

    it('replaces modalidades and jornadas when the establecimiento is updated', function () {
        Storage::fake('public');
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        $presencial = Sys_Modalidad::factory()->create();
        $virtual = Sys_Modalidad::factory()->create();
        $matutina = Sys_Jornada::factory()->create();
        $otro = Sys_Jornada::factory()->create();
        $oferta = EstablecimientoModalidad::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'modalidad_id' => $presencial->id,
        ]);
        $oferta->jornadas()->attach($matutina->id);
        $zona = $establecimiento->zona;
        $distrito = $establecimiento->distrito;
        $circuito = $establecimiento->circuito;

        $this->actingAs($actor)
            ->patch(
                route('sistemas.establecimientos.update', $establecimiento),
                establecimientoPayload($zona, $distrito, $circuito, [
                    'codigo_amie' => $establecimiento->codigo_amie,
                    'email' => $establecimiento->email,
                    'modalidades' => [$virtual->id],
                    'jornadas' => [
                        $virtual->id => [$otro->id],
                    ],
                ]),
            )
            ->assertRedirect(route('sistemas.establecimientos'))
            ->assertSessionHasNoErrors();

        $establecimiento->refresh();
        $ofertas = $establecimiento->establecimientoModalidades()->with('jornadas')->get();

        expect($ofertas)->toHaveCount(1)
            ->and($ofertas->first()->modalidad_id)->toBe($virtual->id)
            ->and($ofertas->first()->jornadas->pluck('id')->all())->toBe([$otro->id]);
    });

    it('prefills the administrator on the edit form', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        assignRole(User::factory()->create([
            'name' => 'Director Andino',
            'email' => 'director@example.com',
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Admin);

        $this->actingAs($actor)
            ->get(route('sistemas.establecimientos.edit', $establecimiento))
            ->assertSee('Director Andino')
            ->assertSee('director@example.com')
            ->assertSee('Nueva contraseña (opcional)');
    });

    it('updates the administrator without requiring a new password', function () {
        Storage::fake('public');
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        $administrador = assignRole(User::factory()->create([
            'name' => 'Director Viejo',
            'email' => 'viejo@example.com',
            'password' => 'secret-password',
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Admin);
        $zona = $establecimiento->zona;
        $distrito = $establecimiento->distrito;
        $circuito = $establecimiento->circuito;

        $payload = establecimientoPayload($zona, $distrito, $circuito, [
            'codigo_amie' => $establecimiento->codigo_amie,
            'email' => $establecimiento->email,
            'admin_name' => 'Director Nuevo',
            'admin_email' => 'nuevo@example.com',
        ]);
        unset($payload['admin_password'], $payload['admin_password_confirmation'], $payload['logo']);

        $this->actingAs($actor)
            ->patch(route('sistemas.establecimientos.update', $establecimiento), $payload)
            ->assertRedirect(route('sistemas.establecimientos'))
            ->assertSessionHasNoErrors();

        $administrador->refresh();

        expect($administrador->name)->toBe('Director Nuevo')
            ->and($administrador->email)->toBe('nuevo@example.com')
            ->and($administrador->establecimiento_id)->toBe($establecimiento->id);

        expect(Hash::check('secret-password', $administrador->password))->toBeTrue();
    });

    it('keeps hidden catalog fields and the current logo when they are omitted', function () {
        Storage::fake('public');
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create([
            'logo' => 'establecimientos/logos/actual.png',
            'only_visible' => 'interno',
            'mision' => 'Mision original',
            'vision' => 'Vision original',
            'ideario' => 'Ideario original',
        ]);
        Storage::disk('public')->put($establecimiento->logo, 'logo-content');
        $zona = $establecimiento->zona;
        $distrito = $establecimiento->distrito;
        $circuito = $establecimiento->circuito;

        $payload = establecimientoPayload($zona, $distrito, $circuito, [
            'codigo_amie' => $establecimiento->codigo_amie,
            'email' => $establecimiento->email,
        ]);
        unset($payload['logo']);

        $this->actingAs($actor)
            ->patch(route('sistemas.establecimientos.update', $establecimiento), $payload)
            ->assertRedirect(route('sistemas.establecimientos'));

        $establecimiento->refresh();

        expect($establecimiento->logo)->toBe('establecimientos/logos/actual.png')
            ->and($establecimiento->only_visible)->toBe('interno')
            ->and($establecimiento->mision)->toBe('Mision original')
            ->and($establecimiento->vision)->toBe('Vision original')
            ->and($establecimiento->ideario)->toBe('Ideario original');
    });
});

describe('destroy', function () {
    it('deletes an establecimiento', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();

        $this->actingAs($actor)
            ->delete(route('sistemas.establecimientos.destroy', $establecimiento))
            ->assertRedirect(route('sistemas.establecimientos'))
            ->assertSessionHas('status', 'establecimiento-deleted');

        $this->assertModelMissing($establecimiento);
    });

    it('deletes the administrator when the establecimiento is deleted', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $establecimiento = Establecimiento::factory()->create();
        $administrador = assignRole(User::factory()->create([
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Admin);

        $this->actingAs($actor)
            ->delete(route('sistemas.establecimientos.destroy', $establecimiento))
            ->assertRedirect(route('sistemas.establecimientos'));

        $this->assertModelMissing($establecimiento);
        $this->assertModelMissing($administrador);
    });
});
