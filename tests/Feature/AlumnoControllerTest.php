<?php

use App\Enums\Role;
use App\Models\Alumno;
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
function alumnoPayload(Sys_Pais $pais, Sys_Provincia $provincia, array $overrides = []): array
{
    return [
        'tipo_identificacion_id' => '1',
        'identificacion' => '0912345680',
        'nombres' => 'Ana',
        'apellidos' => 'Pérez',
        'genero_id' => '2',
        'fecha_nacimiento' => '2012-05-10',
        'ciudad_nacimiento' => 'Guayaquil',
        'nacionalidad_id' => $pais->id,
        'provincia_id' => $provincia->id,
        'parroquia' => 'Tarqui',
        'direccion' => 'Av. 9 de Octubre 200',
        'telefono1' => '042000001',
        'telefono2' => '0991111112',
        'lote' => '1',
        'contacto_emergencia' => '0990000000',
        'activo' => '1',
        'email' => 'ana.perez@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        ...$overrides,
    ];
}

describe('index', function () {
    it('allows administrators to open alumnos of their establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $persona = Persona::factory()->create([
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'establecimiento_id' => $establecimiento->id,
        ]);
        Alumno::factory()->for($persona)->create();

        $this->actingAs($admin)
            ->get(route('Admin.alumnos'))
            ->assertOk()
            ->assertSee('Alumnos')
            ->assertSee('Ana')
            ->assertSee('Pérez');
    });

    it('does not list alumnos from another establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $other = Persona::factory()->create([
            'nombres' => 'Otro',
            'apellidos' => 'Colegio',
        ]);
        Alumno::factory()->for($other)->create();

        $this->actingAs($admin)
            ->get(route('Admin.alumnos'))
            ->assertOk()
            ->assertDontSee('Otro')
            ->assertDontSee('Colegio');
    });

    it('forbids systems users from opening alumnos', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get(route('Admin.alumnos'))
            ->assertForbidden();
    });

    it('redirects guests from alumnos to login', function () {
        $this->get(route('Admin.alumnos'))
            ->assertRedirect(route('login'));
    });

    it('forbids administrators without an establishment', function () {
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.alumnos'))
            ->assertForbidden();
    });

    it('escapes field values in the alumnos list', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $persona = Persona::factory()->create([
            'nombres' => "<script>alert('xss')</script>",
            'establecimiento_id' => $establecimiento->id,
        ]);
        Alumno::factory()->for($persona)->create();

        $this->actingAs($admin)
            ->get(route('Admin.alumnos'))
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });
});

describe('create', function () {
    it('shows catalog options and padres of the establishment on the create form', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create(['nombre' => 'Ecuador']);
        Sys_Provincia::factory()->create([
            'nombre' => 'Guayas',
            'pais_id' => $pais->id,
        ]);
        $padrePersona = Persona::factory()->create([
            'nombres' => 'Carlos',
            'apellidos' => 'Ruiz Vega',
            'establecimiento_id' => $establecimiento->id,
        ]);
        Padre::factory()->for($padrePersona)->create();
        $otherPadre = Persona::factory()->create([
            'nombres' => 'Otro',
            'apellidos' => 'Colegio',
        ]);
        Padre::factory()->for($otherPadre)->create();

        $this->actingAs($admin)
            ->get(route('Admin.alumnos.create'))
            ->assertOk()
            ->assertSee('Nuevo alumno')
            ->assertSee('Ecuador')
            ->assertSee('Guayas')
            ->assertSee('Usuario de acceso')
            ->assertSee('Contraseña')
            ->assertSee('Ruiz Vega')
            ->assertDontSee('Colegio');
    });

    it('forbids administrators without an establishment from opening the create form', function () {
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.alumnos.create'))
            ->assertForbidden();
    });
});

describe('store', function () {
    it('forbids systems users from creating alumnos', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->post(route('Admin.alumnos.store'), [])
            ->assertForbidden();
    });

    it('creates an alumno, persona and user with password', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);

        $this->actingAs($admin)
            ->post(route('Admin.alumnos.store'), alumnoPayload($pais, $provincia))
            ->assertRedirect(route('Admin.alumnos'))
            ->assertSessionHas('status', 'alumno-created');

        $user = User::query()->where('email', 'ana.perez@example.com')->firstOrFail();
        $persona = Persona::query()->where('identificacion', '0912345680')->firstOrFail();
        $alumno = Alumno::query()->where('persona_id', $persona->id)->firstOrFail();

        expect($user->name)->toBe('Ana Pérez')
            ->and($user->establecimiento_id)->toBe($establecimiento->id)
            ->and($user->hasRole(Role::Alumno))->toBeTrue();

        expect(Hash::check('password', $user->password))->toBeTrue();

        expect($persona->nombres)->toBe('Ana')
            ->and($persona->apellidos)->toBe('Pérez')
            ->and($persona->user_id)->toBe($user->id)
            ->and($persona->establecimiento_id)->toBe($establecimiento->id)
            ->and($persona->usuario)->toBe('Director Andino');

        expect($alumno->contacto_emergencia)->toBe('0990000000')
            ->and($alumno->padre_id)->toBeNull()
            ->and($alumno->activo)->toBe(1);
    });

    it('assigns a padre of the same establishment when creating an alumno', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $padrePersona = Persona::factory()->create([
            'establecimiento_id' => $establecimiento->id,
        ]);
        $padre = Padre::factory()->for($padrePersona)->create();

        $this->actingAs($admin)
            ->post(route('Admin.alumnos.store'), alumnoPayload($pais, $provincia, [
                'padre_id' => $padre->id,
            ]))
            ->assertRedirect(route('Admin.alumnos'))
            ->assertSessionHas('status', 'alumno-created');

        $persona = Persona::query()->where('identificacion', '0912345680')->firstOrFail();
        $alumno = Alumno::query()->where('persona_id', $persona->id)->firstOrFail();

        expect($alumno->padre_id)->toBe($padre->id);
        $this->assertDatabaseHas('padres', ['id' => $padre->id]);
    });

    it('does not create records when the padre belongs to another establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $padre = Padre::factory()->create();

        $this->actingAs($admin)
            ->from(route('Admin.alumnos.create'))
            ->post(route('Admin.alumnos.store'), alumnoPayload($pais, $provincia, [
                'padre_id' => $padre->id,
            ]))
            ->assertRedirect(route('Admin.alumnos.create'))
            ->assertSessionHasErrors('padre_id');

        $this->assertDatabaseMissing('users', ['email' => 'ana.perez@example.com']);
        $this->assertDatabaseMissing('personas', ['identificacion' => '0912345680']);
    });

    it('does not create records when the email is already taken', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        User::factory()->create(['email' => 'ana.perez@example.com']);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);

        $this->actingAs($admin)
            ->from(route('Admin.alumnos.create'))
            ->post(route('Admin.alumnos.store'), alumnoPayload($pais, $provincia))
            ->assertRedirect(route('Admin.alumnos.create'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('personas', ['identificacion' => '0912345680']);
        $this->assertDatabaseMissing('alumnos', ['contacto_emergencia' => '0990000000']);
    });

    it('does not create records when required fields are missing', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->from(route('Admin.alumnos.create'))
            ->post(route('Admin.alumnos.store'), [])
            ->assertRedirect(route('Admin.alumnos.create'))
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
                'contacto_emergencia',
                'email',
                'password',
            ]);
    });
});

describe('update', function () {
    it('updates the alumno, persona, user and assigned padre', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $user = assignRole(User::factory()->create([
            'email' => 'ana.perez@example.com',
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Alumno);
        $persona = Persona::factory()->create([
            'user_id' => $user->id,
            'identificacion' => '0912345680',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'establecimiento_id' => $establecimiento->id,
            'nacionalidad_id' => $pais->id,
            'provincia_id' => $provincia->id,
        ]);
        $alumno = Alumno::factory()->for($persona)->create([
            'contacto_emergencia' => '0990000000',
        ]);
        $padrePersona = Persona::factory()->create([
            'establecimiento_id' => $establecimiento->id,
        ]);
        $padre = Padre::factory()->for($padrePersona)->create();

        $this->actingAs($admin)
            ->patch(route('Admin.alumnos.update', $alumno), alumnoPayload($pais, $provincia, [
                'nombres' => 'Anita',
                'apellidos' => 'Pérez Vega',
                'contacto_emergencia' => '0981111111',
                'email' => 'anita.perez@example.com',
                'password' => '',
                'password_confirmation' => '',
                'padre_id' => $padre->id,
            ]))
            ->assertRedirect(route('Admin.alumnos'))
            ->assertSessionHas('status', 'alumno-updated');

        $alumno->refresh();
        $persona->refresh();
        $user->refresh();

        expect($alumno->contacto_emergencia)->toBe('0981111111')
            ->and($alumno->padre_id)->toBe($padre->id)
            ->and($persona->nombres)->toBe('Anita')
            ->and($persona->apellidos)->toBe('Pérez Vega')
            ->and($user->email)->toBe('anita.perez@example.com')
            ->and($user->name)->toBe('Anita Pérez Vega');
    });

    it('returns 404 when editing an alumno from another establishment', function () {
        $admin = adminOf(Establecimiento::factory()->create());
        $alumno = Alumno::factory()->create();

        $this->actingAs($admin)
            ->get(route('Admin.alumnos.edit', $alumno))
            ->assertNotFound();
    });
});

describe('destroy', function () {
    it('deletes the alumno, persona and user without deleting the padre', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $user = assignRole(User::factory()->create([
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Alumno);
        $persona = Persona::factory()->create([
            'user_id' => $user->id,
            'establecimiento_id' => $establecimiento->id,
        ]);
        $padrePersona = Persona::factory()->create([
            'establecimiento_id' => $establecimiento->id,
        ]);
        $padre = Padre::factory()->for($padrePersona)->create();
        $alumno = Alumno::factory()->for($persona)->create([
            'padre_id' => $padre->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('Admin.alumnos.destroy', $alumno))
            ->assertRedirect(route('Admin.alumnos'))
            ->assertSessionHas('status', 'alumno-deleted');

        $this->assertDatabaseMissing('alumnos', ['id' => $alumno->id]);
        $this->assertDatabaseMissing('personas', ['id' => $persona->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('padres', ['id' => $padre->id]);
    });

    it('returns 404 when deleting an alumno from another establishment', function () {
        $admin = adminOf(Establecimiento::factory()->create());
        $alumno = Alumno::factory()->create();

        $this->actingAs($admin)
            ->delete(route('Admin.alumnos.destroy', $alumno))
            ->assertNotFound();

        $this->assertDatabaseHas('alumnos', ['id' => $alumno->id]);
    });
});
