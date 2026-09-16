<?php

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\Sys_Circuito;
use App\Models\Sys_Distrito;
use App\Models\Sys_Zona;
use App\Models\User;

/**
 * @return array<string, mixed>
 */
function establecimientoPayload(Sys_Zona $zona, Sys_Distrito $distrito, Sys_Circuito $circuito, array $overrides = []): array
{
    return [
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
        'logo' => 'logo.png',
        'zona_id' => $zona->id,
        'distrito_id' => $distrito->id,
        'circuito_id' => $circuito->id,
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
});

describe('store', function () {
    it('creates an establecimiento with catalog ids not names', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);
        $zona = Sys_Zona::factory()->create();
        $distrito = Sys_Distrito::factory()->create(['zona_id' => $zona->id]);
        $circuito = Sys_Circuito::factory()->create(['distrito_id' => $distrito->id]);

        $this->actingAs($actor)
            ->post(route('sistemas.establecimientos.store'), establecimientoPayload($zona, $distrito, $circuito))
            ->assertRedirect(route('sistemas.establecimientos'))
            ->assertSessionHas('status', 'establecimiento-created');

        $this->assertDatabaseHas('establecimientos', [
            'nombre' => 'UE Los Andes',
            'email' => 'andes@example.com',
            'zona_id' => $zona->id,
            'distrito_id' => $distrito->id,
            'circuito_id' => $circuito->id,
        ]);
    });

    it('rejects an empty payload', function () {
        $actor = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($actor)
            ->from(route('sistemas.establecimientos.create'))
            ->post(route('sistemas.establecimientos.store'), [])
            ->assertRedirect(route('sistemas.establecimientos.create'))
            ->assertSessionHasErrors(['nombre', 'zona_id', 'distrito_id', 'circuito_id']);
    });

    it('rejects a distrito that does not belong to the zona', function () {
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
});

describe('update', function () {
    it('updates an establecimiento', function () {
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
});
