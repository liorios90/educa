<?php

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\Padre;
use App\Models\Persona;
use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * @return array<string, mixed>
 */
function padrePayload(Sys_Pais $pais, Sys_Provincia $provincia, array $overrides = []): array
{
    return [
        'tipo_identificacion_id' => '1',
        'identificacion' => '0912345678',
        'nombres' => 'Carlos',
        'apellidos' => 'Ruiz',
        'genero_id' => '1',
        'fecha_nacimiento' => '1975-03-20',
        'ciudad_nacimiento' => 'Guayaquil',
        'nacionalidad_id' => $pais->id,
        'provincia_id' => $provincia->id,
        'parroquia' => 'Tarqui',
        'direccion' => 'Av. 9 de Octubre 100',
        'telefono1' => '042000000',
        'telefono2' => '0991111111',
        'lote' => '1',
        'estado_civil_id' => 'Casado',
        'vive_con_estudiante' => '1',
        'titulo' => 'Ingeniero',
        'activo' => '1',
        'email' => 'carlos.ruiz@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        ...$overrides,
    ];
}

describe('index', function () {
    it('allows administrators to open padres of their establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $persona = Persona::factory()->create([
            'nombres' => 'Carlos',
            'apellidos' => 'Ruiz',
            'establecimiento_id' => $establecimiento->id,
        ]);
        Padre::factory()->for($persona)->create();

        $this->actingAs($admin)
            ->get(route('Admin.padres'))
            ->assertOk()
            ->assertSee('Padres de familia')
            ->assertSee('Carlos')
            ->assertSee('Ruiz');
    });

    it('does not list padres from another establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $other = Persona::factory()->create([
            'nombres' => 'Otro',
            'apellidos' => 'Colegio',
        ]);
        Padre::factory()->for($other)->create();

        $this->actingAs($admin)
            ->get(route('Admin.padres'))
            ->assertOk()
            ->assertDontSee('Otro')
            ->assertDontSee('Colegio');
    });

    it('forbids systems users from opening padres', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get(route('Admin.padres'))
            ->assertForbidden();
    });

    it('redirects guests from padres to login', function () {
        $this->get(route('Admin.padres'))
            ->assertRedirect(route('login'));
    });

    it('forbids administrators without an establishment', function () {
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.padres'))
            ->assertForbidden();
    });

    it('escapes field values in the padres list', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $persona = Persona::factory()->create([
            'nombres' => "<script>alert('xss')</script>",
            'establecimiento_id' => $establecimiento->id,
        ]);
        Padre::factory()->for($persona)->create();

        $this->actingAs($admin)
            ->get(route('Admin.padres'))
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });
});

describe('create', function () {
    it('shows catalog options on the create form', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create(['nombre' => 'Ecuador']);
        Sys_Provincia::factory()->create([
            'nombre' => 'Guayas',
            'pais_id' => $pais->id,
        ]);

        $this->actingAs($admin)
            ->get(route('Admin.padres.create'))
            ->assertOk()
            ->assertSee('Nuevo padre de familia')
            ->assertSee('Ecuador')
            ->assertSee('Guayas')
            ->assertSee('Usuario de acceso')
            ->assertSee('Contraseña');
    });

    it('forbids administrators without an establishment from opening the create form', function () {
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.padres.create'))
            ->assertForbidden();
    });
});

describe('store', function () {
    it('forbids systems users from creating padres', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->post(route('Admin.padres.store'), [])
            ->assertForbidden();
    });

    it('creates a padre, persona and user with password', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);

        $this->actingAs($admin)
            ->post(route('Admin.padres.store'), padrePayload($pais, $provincia))
            ->assertRedirect(route('Admin.padres'))
            ->assertSessionHas('status', 'padre-created');

        $user = User::query()->where('email', 'carlos.ruiz@example.com')->firstOrFail();
        $persona = Persona::query()->where('identificacion', '0912345678')->firstOrFail();
        $padre = Padre::query()->where('persona_id', $persona->id)->firstOrFail();

        expect($user->name)->toBe('Carlos Ruiz')
            ->and($user->establecimiento_id)->toBe($establecimiento->id)
            ->and($user->hasRole(Role::Padre))->toBeTrue();

        expect(Hash::check('password', $user->password))->toBeTrue();

        expect($persona->nombres)->toBe('Carlos')
            ->and($persona->apellidos)->toBe('Ruiz')
            ->and($persona->user_id)->toBe($user->id)
            ->and($persona->establecimiento_id)->toBe($establecimiento->id)
            ->and($persona->nacionalidad_id)->toBe($pais->id)
            ->and($persona->provincia_id)->toBe($provincia->id)
            ->and($persona->usuario)->toBe('Director Andino');

        expect($padre->titulo)->toBe('Ingeniero')
            ->and($padre->estado_civil_id)->toBe('Casado')
            ->and($padre->vive_con_estudiante)->toBe(1)
            ->and($padre->activo)->toBe(1);
    });

    it('does not create records when the email is already taken', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        User::factory()->create(['email' => 'carlos.ruiz@example.com']);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);

        $this->actingAs($admin)
            ->from(route('Admin.padres.create'))
            ->post(route('Admin.padres.store'), padrePayload($pais, $provincia))
            ->assertRedirect(route('Admin.padres.create'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('personas', ['identificacion' => '0912345678']);
        $this->assertDatabaseMissing('padres', ['titulo' => 'Ingeniero']);
    });

    it('does not create records when required fields are missing', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->from(route('Admin.padres.create'))
            ->post(route('Admin.padres.store'), [])
            ->assertRedirect(route('Admin.padres.create'))
            ->assertSessionHasErrors([
                'tipo_identificacion_id',
                'identificacion',
                'nombres',
                'apellidos',
                'genero_id',
                'ciudad_nacimiento',
                'nacionalidad_id',
                'provincia_id',
                'parroquia',
                'direccion',
                'telefono1',
                'telefono2',
                'estado_civil_id',
                'titulo',
                'email',
                'password',
            ]);
    });

    it('rejects a provincia that does not belong to the selected country', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $otroPais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $otroPais->id]);

        $this->actingAs($admin)
            ->from(route('Admin.padres.create'))
            ->post(route('Admin.padres.store'), padrePayload($pais, $provincia))
            ->assertRedirect(route('Admin.padres.create'))
            ->assertSessionHasErrors('provincia_id');

        $this->assertDatabaseMissing('users', ['email' => 'carlos.ruiz@example.com']);
    });
});

describe('update', function () {
    it('updates the padre, persona and user', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $user = assignRole(User::factory()->create([
            'email' => 'carlos.ruiz@example.com',
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Padre);
        $persona = Persona::factory()->create([
            'user_id' => $user->id,
            'identificacion' => '0912345678',
            'nombres' => 'Carlos',
            'apellidos' => 'Ruiz',
            'establecimiento_id' => $establecimiento->id,
            'nacionalidad_id' => $pais->id,
            'provincia_id' => $provincia->id,
        ]);
        $padre = Padre::factory()->for($persona)->create(['titulo' => 'Ingeniero']);

        $this->actingAs($admin)
            ->patch(route('Admin.padres.update', $padre), padrePayload($pais, $provincia, [
                'nombres' => 'Carla',
                'apellidos' => 'Ruiz Vega',
                'titulo' => 'Doctora',
                'email' => 'carla.ruiz@example.com',
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertRedirect(route('Admin.padres'))
            ->assertSessionHas('status', 'padre-updated');

        $padre->refresh();
        $persona->refresh();
        $user->refresh();

        expect($padre->titulo)->toBe('Doctora')
            ->and($persona->nombres)->toBe('Carla')
            ->and($persona->apellidos)->toBe('Ruiz Vega')
            ->and($user->email)->toBe('carla.ruiz@example.com')
            ->and($user->name)->toBe('Carla Ruiz Vega');
    });

    it('returns 404 when editing a padre from another establishment', function () {
        $admin = adminOf(Establecimiento::factory()->create());
        $padre = Padre::factory()->create();

        $this->actingAs($admin)
            ->get(route('Admin.padres.edit', $padre))
            ->assertNotFound();
    });
});

describe('destroy', function () {
    it('deletes the padre, persona and user', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $user = assignRole(User::factory()->create([
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Padre);
        $persona = Persona::factory()->create([
            'user_id' => $user->id,
            'establecimiento_id' => $establecimiento->id,
        ]);
        $padre = Padre::factory()->for($persona)->create();

        $this->actingAs($admin)
            ->delete(route('Admin.padres.destroy', $padre))
            ->assertRedirect(route('Admin.padres'))
            ->assertSessionHas('status', 'padre-deleted');

        $this->assertDatabaseMissing('padres', ['id' => $padre->id]);
        $this->assertDatabaseMissing('personas', ['id' => $persona->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    });

    it('returns 404 when deleting a padre from another establishment', function () {
        $admin = adminOf(Establecimiento::factory()->create());
        $padre = Padre::factory()->create();

        $this->actingAs($admin)
            ->delete(route('Admin.padres.destroy', $padre))
            ->assertNotFound();

        $this->assertDatabaseHas('padres', ['id' => $padre->id]);
    });
});
