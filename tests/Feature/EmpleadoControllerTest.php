<?php

use App\Enums\Role;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\Persona;
use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Funcion;
use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;
use App\Models\Sys_Subnivel;
use App\Models\Sys_TipoContrato;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * @return array<string, mixed>
 */
function empleadoPayload(Sys_Pais $pais, Sys_Provincia $provincia, Sys_TipoContrato $tipoContrato, array $overrides = []): array
{
    return [
        'tipo_identificacion_id' => '1',
        'identificacion' => '0912345690',
        'nombres' => 'Luis',
        'apellidos' => 'Mora',
        'genero_id' => '1',
        'fecha_nacimiento' => '1985-03-12',
        'ciudad_nacimiento' => 'Quito',
        'nacionalidad_id' => $pais->id,
        'provincia_id' => $provincia->id,
        'parroquia' => 'Iñaquito',
        'direccion' => 'Av. Amazonas 100',
        'telefono1' => '022000001',
        'telefono2' => '0991111113',
        'lote' => '1',
        'tipo_contrato_id' => $tipoContrato->id,
        'cargo_id' => '12',
        'horas' => '40',
        'anios_experiencia' => '8',
        'anios_instituto' => '3',
        'contacto_emergencia' => 'María Mora',
        'contacto_num' => '0990000001',
        'activo' => '1',
        'email' => 'luis.mora@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [Role::Empleado->value],
        ...$overrides,
    ];
}

/**
 * @return array{0: EstablecimientoModalidadJornada, 1: Sys_Asignatura}
 */
function especialidadEnJornada(Establecimiento $establecimiento, string $nombre): array
{
    $oferta = ofertaDe($establecimiento);
    $asignatura = Sys_Asignatura::factory()->create(['nombre' => $nombre]);
    EstablecimientoAsignatura::factory()->create([
        'establecimiento_id' => $establecimiento->id,
        'establecimiento_modalidad_jornada_id' => $oferta->id,
        'asignatura_id' => $asignatura->id,
    ]);

    return [$oferta, $asignatura];
}

/**
 * @return array{0: EstablecimientoModalidadJornada, 1: Sys_Asignatura, 2: Sys_Asignatura}
 */
function especialidadesDeUnArea(Establecimiento $establecimiento): array
{
    $oferta = ofertaDe($establecimiento);
    $elemental = Sys_Subnivel::factory()->create(['nombre' => 'Básica Elemental']);
    $media = Sys_Subnivel::factory()->create(['nombre' => 'Básica Media']);
    $areaElemental = Sys_Area::factory()->create([
        'nombre' => 'Matemática',
        'orden' => 1,
        'subnivel_id' => $elemental->id,
    ]);
    $areaMedia = Sys_Area::factory()->create([
        'nombre' => 'Matemática',
        'orden' => 1,
        'subnivel_id' => $media->id,
    ]);
    $matematicaElemental = Sys_Asignatura::factory()->create([
        'nombre' => 'Matemática',
        'area_id' => $areaElemental->id,
    ]);
    $matematicaMedia = Sys_Asignatura::factory()->create([
        'nombre' => 'Matemática',
        'area_id' => $areaMedia->id,
    ]);

    foreach ([$matematicaElemental, $matematicaMedia] as $asignatura) {
        EstablecimientoAsignatura::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $oferta->id,
            'asignatura_id' => $asignatura->id,
        ]);
    }

    return [$oferta, $matematicaElemental, $matematicaMedia];
}

describe('index', function () {
    it('allows administrators to open empleados of their establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $persona = Persona::factory()->create([
            'nombres' => 'Luis',
            'apellidos' => 'Mora',
            'establecimiento_id' => $establecimiento->id,
        ]);
        $empleado = Empleado::factory()->for($persona)->create();

        $this->actingAs($admin)
            ->get(route('Admin.empleados'))
            ->assertOk()
            ->assertSee('Empleados')
            ->assertSee('Importar Excel')
            ->assertSee('Luis')
            ->assertSee('Mora')
            ->assertSee('Editar')
            ->assertSee('Eliminar')
            ->assertSee(route('Admin.empleados.edit', $empleado), false)
            ->assertSee(route('Admin.empleados.destroy', $empleado), false);
    });

    it('shows assigned roles in the empleados list', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $user = assignRole(User::factory()->create([
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Secretaria);
        assignRole($user, Role::Admin);
        $persona = Persona::factory()->create([
            'nombres' => 'Luis',
            'apellidos' => 'Mora',
            'establecimiento_id' => $establecimiento->id,
            'user_id' => $user->id,
        ]);
        Empleado::factory()->for($persona)->create();

        $this->actingAs($admin)
            ->get(route('Admin.empleados'))
            ->assertSee('Secretaría')
            ->assertSee('Administrador');
    });

    it('does not list empleados from another establishment', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $other = Persona::factory()->create([
            'nombres' => 'Otro',
            'apellidos' => 'Colegio',
        ]);
        Empleado::factory()->for($other)->create();

        $this->actingAs($admin)
            ->get(route('Admin.empleados'))
            ->assertOk()
            ->assertDontSee('Otro')
            ->assertDontSee('Colegio');
    });

    it('forbids systems users from opening empleados', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->get(route('Admin.empleados'))
            ->assertForbidden();
    });

    it('redirects guests from empleados to login', function () {
        $this->get(route('Admin.empleados'))
            ->assertRedirect(route('login'));
    });

    it('forbids administrators without an establishment', function () {
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.empleados'))
            ->assertForbidden();
    });

    it('escapes field values in the empleados list', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $persona = Persona::factory()->create([
            'nombres' => "<script>alert('xss')</script>",
            'establecimiento_id' => $establecimiento->id,
        ]);
        Empleado::factory()->for($persona)->create();

        $this->actingAs($admin)
            ->get(route('Admin.empleados'))
            ->assertSee("<script>alert('xss')</script>")
            ->assertDontSee("<script>alert('xss')</script>", false);
    });

    it('lists each specialty with its subnivel', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        [, $elemental, $media] = especialidadesDeUnArea($establecimiento);
        $persona = Persona::factory()->create([
            'establecimiento_id' => $establecimiento->id,
        ]);
        $empleado = Empleado::factory()->for($persona)->create();
        $empleado->especialidades()->attach([$elemental->id, $media->id]);

        $this->actingAs($admin)
            ->get(route('Admin.empleados'))
            ->assertOk()
            ->assertSee('Básica Elemental · Matemática')
            ->assertSee('Básica Media · Matemática')
            ->assertDontSee('Matemática · Matemática');
    });
});

describe('create', function () {
    it('shows catalog options on the create form', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create(['nombre' => 'Ecuador']);
        Sys_Provincia::factory()->create([
            'nombre' => 'Pichincha',
            'pais_id' => $pais->id,
        ]);
        Sys_TipoContrato::factory()->create(['nombre' => 'Nombramiento']);
        Sys_Funcion::factory()->create(['nombre' => 'Docente']);

        $this->actingAs($admin)
            ->get(route('Admin.empleados.create'))
            ->assertOk()
            ->assertSee('Nuevo empleado')
            ->assertSee('Ecuador')
            ->assertSee('Pichincha')
            ->assertSee('Nombramiento')
            ->assertSee('Docente')
            ->assertSee('Usuario de acceso')
            ->assertSee('Contraseña')
            ->assertSee('name="roles[]"', false)
            ->assertSee('Administrador')
            ->assertSee('Sistemas')
            ->assertSee('Secretaría')
            ->assertSee('Empleado')
            ->assertSee('value="Docente"', false)
            ->assertSee('Especialidad')
            ->assertDontSee('>Cargo<', false)
            ->assertDontSee('>Lote<', false)
            ->assertSee('Define la malla de asignaturas de esta jornada antes de registrar la especialidad.')
            ->assertDontSee('Padre')
            ->assertDontSee('Alumno');
    });

    it('shows each subnivel of an area instead of repeating the subject name', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        [$oferta] = especialidadesDeUnArea($establecimiento);

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->get(route('Admin.empleados.create'))
            ->assertOk()
            ->assertSee('Selecciona un área')
            ->assertSee('Subnivel: Básica Elemental')
            ->assertSee('Subnivel: Básica Media')
            ->assertDontSee('Matemática · Matemática')
            ->assertDontSee('Matemática - Matemática');
    });

    it('forbids administrators without an establishment from opening the create form', function () {
        $admin = assignRole(User::factory()->create(['establecimiento_id' => null]), Role::Admin);

        $this->actingAs($admin)
            ->get(route('Admin.empleados.create'))
            ->assertForbidden();
    });
});

describe('store', function () {
    it('forbids systems users from creating empleados', function () {
        $user = assignRole(User::factory()->create(), Role::Sistemas);

        $this->actingAs($user)
            ->post(route('Admin.empleados.store'), [])
            ->assertForbidden();
    });

    it('creates an empleado, persona and user with password', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $tipoContrato = Sys_TipoContrato::factory()->create();
        $funcion = Sys_Funcion::factory()->create();

        $this->actingAs($admin)
            ->post(route('Admin.empleados.store'), empleadoPayload($pais, $provincia, $tipoContrato, [
                'funcion_id' => $funcion->id,
            ]))
            ->assertRedirect(route('Admin.empleados'))
            ->assertSessionHas('status', 'empleado-created');

        $user = User::query()->where('email', 'luis.mora@example.com')->firstOrFail();
        $persona = Persona::query()->where('identificacion', '0912345690')->firstOrFail();
        $empleado = Empleado::query()->where('persona_id', $persona->id)->firstOrFail();

        expect($user->name)->toBe('Luis Mora')
            ->and($user->establecimiento_id)->toBe($establecimiento->id)
            ->and($user->hasRole(Role::Empleado))->toBeTrue();

        expect(Hash::check('password', $user->password))->toBeTrue();

        expect($persona->nombres)->toBe('Luis')
            ->and($persona->apellidos)->toBe('Mora')
            ->and($persona->user_id)->toBe($user->id)
            ->and($persona->establecimiento_id)->toBe($establecimiento->id)
            ->and($persona->usuario)->toBe('Director Andino');

        expect($empleado->tipo_contrato_id)->toBe($tipoContrato->id)
            ->and($empleado->funcion_id)->toBe($funcion->id)
            ->and($empleado->cargo_id)->toBe(12)
            ->and($empleado->horas)->toBe(40)
            ->and($empleado->anios_experiencia)->toBe(8)
            ->and($empleado->anios_instituto)->toBe(3)
            ->and($empleado->contacto_emergencia)->toBe('María Mora')
            ->and($empleado->contacto_num)->toBe('0990000001')
            ->and($empleado->usuario)->toBe('Director Andino')
            ->and($empleado->activo)->toBe(1);
    });

    it('creates an empleado without a funcion', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $tipoContrato = Sys_TipoContrato::factory()->create();

        $this->actingAs($admin)
            ->post(route('Admin.empleados.store'), empleadoPayload($pais, $provincia, $tipoContrato))
            ->assertRedirect(route('Admin.empleados'))
            ->assertSessionHas('status', 'empleado-created');

        $persona = Persona::query()->where('identificacion', '0912345690')->firstOrFail();
        $empleado = Empleado::query()->where('persona_id', $persona->id)->firstOrFail();

        expect($empleado->funcion_id)->toBeNull();
    });

    it('assigns the selected roles instead of forcing Empleado', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $tipoContrato = Sys_TipoContrato::factory()->create();

        $this->actingAs($admin)
            ->post(route('Admin.empleados.store'), empleadoPayload($pais, $provincia, $tipoContrato, [
                'roles' => [Role::Secretaria->value],
            ]))
            ->assertRedirect(route('Admin.empleados'))
            ->assertSessionHas('status', 'empleado-created');

        $user = User::query()->where('email', 'luis.mora@example.com')->firstOrFail();

        expect($user->hasRole(Role::Secretaria))->toBeTrue()
            ->and($user->hasRole(Role::Empleado))->toBeFalse();
    });

    it('assigns the docente role and every checked subject of the session malla', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $tipoContrato = Sys_TipoContrato::factory()->create();
        [$oferta, $elemental, $media] = especialidadesDeUnArea($establecimiento);

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->post(route('Admin.empleados.store'), empleadoPayload($pais, $provincia, $tipoContrato, [
                'roles' => [Role::Docente->value],
                'asignaturas' => [$elemental->id, $media->id],
            ]))
            ->assertRedirect(route('Admin.empleados'))
            ->assertSessionHas('status', 'empleado-created');

        $user = User::query()->where('email', 'luis.mora@example.com')->firstOrFail();
        $empleado = Empleado::query()
            ->whereHas('persona', fn ($query) => $query->where('user_id', $user->id))
            ->first();

        expect($user->hasRole(Role::Docente))->toBeTrue()
            ->and($user->establecimiento_id)->toBe($establecimiento->id)
            ->and($empleado?->especialidades->pluck('id')->all())->toBe([$elemental->id, $media->id]);
    });

    it('rejects an active docente without a specialty', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $tipoContrato = Sys_TipoContrato::factory()->create();
        [$oferta] = especialidadEnJornada($establecimiento, 'Matemática');

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->from(route('Admin.empleados.create'))
            ->post(route('Admin.empleados.store'), empleadoPayload($pais, $provincia, $tipoContrato, [
                'roles' => [Role::Docente->value],
            ]))
            ->assertRedirect(route('Admin.empleados.create'))
            ->assertSessionHasErrors([
                'asignaturas' => 'Marca las asignaturas de la especialidad del docente.',
            ]);
    });

    it('rejects a specialty that is not in the session malla', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $tipoContrato = Sys_TipoContrato::factory()->create();
        [$oferta] = especialidadEnJornada($establecimiento, 'Matemática');
        $ajena = ofertaDe($establecimiento, ['jornada' => 'Vespertina']);
        $otra = Sys_Asignatura::factory()->create(['nombre' => 'Ciencias Naturales']);
        EstablecimientoAsignatura::factory()->create([
            'establecimiento_id' => $establecimiento->id,
            'establecimiento_modalidad_jornada_id' => $ajena->id,
            'asignatura_id' => $otra->id,
        ]);

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->from(route('Admin.empleados.create'))
            ->post(route('Admin.empleados.store'), empleadoPayload($pais, $provincia, $tipoContrato, [
                'roles' => [Role::Docente->value],
                'asignaturas' => [$otra->id],
            ]))
            ->assertRedirect(route('Admin.empleados.create'))
            ->assertSessionHasErrors('asignaturas');

        expect(Empleado::query()->count())->toBe(0);
    });

    it('does not keep a specialty when the empleado is not a docente', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $tipoContrato = Sys_TipoContrato::factory()->create();
        [$oferta, $asignatura] = especialidadEnJornada($establecimiento, 'Matemática');

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->post(route('Admin.empleados.store'), empleadoPayload($pais, $provincia, $tipoContrato, [
                'roles' => [Role::Secretaria->value],
                'asignaturas' => [$asignatura->id],
            ]))
            ->assertRedirect(route('Admin.empleados'));

        expect(Empleado::query()->first()?->especialidades)->toHaveCount(0);
    });

    it('allows an inactive docente without a specialty', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $tipoContrato = Sys_TipoContrato::factory()->create();
        [$oferta] = especialidadEnJornada($establecimiento, 'Matemática');

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->post(route('Admin.empleados.store'), empleadoPayload($pais, $provincia, $tipoContrato, [
                'email' => 'inactivo.docente@example.com',
                'identificacion' => '0912345699',
                'roles' => [Role::Docente->value],
                'activo' => '0',
            ]))
            ->assertRedirect(route('Admin.empleados'));

        expect(Empleado::query()->first()?->especialidades)->toHaveCount(0)
            ->and(Empleado::query()->first()?->activo)->toBe(0);
    });

    it('assigns multiple selected roles', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $tipoContrato = Sys_TipoContrato::factory()->create();

        $this->actingAs($admin)
            ->post(route('Admin.empleados.store'), empleadoPayload($pais, $provincia, $tipoContrato, [
                'roles' => [Role::Admin->value, Role::Secretaria->value],
            ]))
            ->assertRedirect(route('Admin.empleados'))
            ->assertSessionHas('status', 'empleado-created');

        $user = User::query()->where('email', 'luis.mora@example.com')->firstOrFail();

        expect($user->hasRole(Role::Admin))->toBeTrue()
            ->and($user->hasRole(Role::Secretaria))->toBeTrue()
            ->and($user->hasRole(Role::Empleado))->toBeFalse();
    });

    it('does not create records when no role is selected', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $tipoContrato = Sys_TipoContrato::factory()->create();

        $this->actingAs($admin)
            ->from(route('Admin.empleados.create'))
            ->post(route('Admin.empleados.store'), empleadoPayload($pais, $provincia, $tipoContrato, [
                'roles' => [],
            ]))
            ->assertRedirect(route('Admin.empleados.create'))
            ->assertSessionHasErrors('roles');

        $this->assertDatabaseMissing('users', ['email' => 'luis.mora@example.com']);
        $this->assertDatabaseMissing('personas', ['identificacion' => '0912345690']);
    });

    it('does not create records when the role is not assignable to an empleado', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $tipoContrato = Sys_TipoContrato::factory()->create();

        $this->actingAs($admin)
            ->from(route('Admin.empleados.create'))
            ->post(route('Admin.empleados.store'), empleadoPayload($pais, $provincia, $tipoContrato, [
                'roles' => [Role::Padre->value],
            ]))
            ->assertRedirect(route('Admin.empleados.create'))
            ->assertSessionHasErrors('roles.0');

        $this->assertDatabaseMissing('users', ['email' => 'luis.mora@example.com']);
        $this->assertDatabaseMissing('personas', ['identificacion' => '0912345690']);
    });

    it('does not create records when the email is already taken', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        User::factory()->create(['email' => 'luis.mora@example.com']);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $tipoContrato = Sys_TipoContrato::factory()->create();

        $this->actingAs($admin)
            ->from(route('Admin.empleados.create'))
            ->post(route('Admin.empleados.store'), empleadoPayload($pais, $provincia, $tipoContrato))
            ->assertRedirect(route('Admin.empleados.create'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('personas', ['identificacion' => '0912345690']);
        $this->assertDatabaseMissing('empleados', ['contacto_num' => '0990000001']);
    });

    it('does not create records when required fields are missing', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);

        $this->actingAs($admin)
            ->from(route('Admin.empleados.create'))
            ->post(route('Admin.empleados.store'), [])
            ->assertRedirect(route('Admin.empleados.create'))
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
                'tipo_contrato_id',
                'horas',
                'anios_experiencia',
                'anios_instituto',
                'contacto_emergencia',
                'contacto_num',
                'roles',
                'email',
                'password',
            ]);
    });
});

describe('update', function () {
    it('updates the empleado, persona and user', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $tipoContrato = Sys_TipoContrato::factory()->create();
        $nuevoTipoContrato = Sys_TipoContrato::factory()->create();
        $funcion = Sys_Funcion::factory()->create();
        $user = assignRole(User::factory()->create([
            'email' => 'luis.mora@example.com',
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Empleado);
        $persona = Persona::factory()->create([
            'user_id' => $user->id,
            'identificacion' => '0912345690',
            'nombres' => 'Luis',
            'apellidos' => 'Mora',
            'establecimiento_id' => $establecimiento->id,
            'nacionalidad_id' => $pais->id,
            'provincia_id' => $provincia->id,
        ]);
        $empleado = Empleado::factory()->for($persona)->create([
            'tipo_contrato_id' => $tipoContrato->id,
            'contacto_emergencia' => 'María Mora',
            'contacto_num' => '0990000001',
        ]);

        $this->actingAs($admin)
            ->patch(route('Admin.empleados.update', $empleado), empleadoPayload($pais, $provincia, $nuevoTipoContrato, [
                'nombres' => 'Luis Alberto',
                'apellidos' => 'Mora Vega',
                'funcion_id' => $funcion->id,
                'horas' => '30',
                'contacto_emergencia' => 'Ana Mora',
                'contacto_num' => '0981111112',
                'email' => 'luis.mora.vega@example.com',
                'password' => '',
                'password_confirmation' => '',
                'roles' => [Role::Admin->value, Role::Secretaria->value],
            ]))
            ->assertRedirect(route('Admin.empleados'))
            ->assertSessionHas('status', 'empleado-updated');

        $empleado->refresh();
        $persona->refresh();
        $user->refresh();

        expect($empleado->tipo_contrato_id)->toBe($nuevoTipoContrato->id)
            ->and($empleado->funcion_id)->toBe($funcion->id)
            ->and($empleado->horas)->toBe(30)
            ->and($empleado->contacto_emergencia)->toBe('Ana Mora')
            ->and($empleado->contacto_num)->toBe('0981111112')
            ->and($persona->nombres)->toBe('Luis Alberto')
            ->and($persona->apellidos)->toBe('Mora Vega')
            ->and($user->email)->toBe('luis.mora.vega@example.com')
            ->and($user->name)->toBe('Luis Alberto Mora Vega')
            ->and($user->hasRole(Role::Admin))->toBeTrue()
            ->and($user->hasRole(Role::Secretaria))->toBeTrue()
            ->and($user->hasRole(Role::Empleado))->toBeFalse();
    });

    it('replaces the specialties of a docente', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $pais = Sys_Pais::factory()->create();
        $provincia = Sys_Provincia::factory()->create(['pais_id' => $pais->id]);
        $tipoContrato = Sys_TipoContrato::factory()->create();
        [$oferta, $elemental, $media] = especialidadesDeUnArea($establecimiento);
        $user = assignRole(User::factory()->create([
            'email' => 'luis.mora@example.com',
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Docente);
        $persona = Persona::factory()->create([
            'user_id' => $user->id,
            'identificacion' => '0912345690',
            'establecimiento_id' => $establecimiento->id,
            'nacionalidad_id' => $pais->id,
            'provincia_id' => $provincia->id,
        ]);
        $empleado = Empleado::factory()->for($persona)->create([
            'tipo_contrato_id' => $tipoContrato->id,
        ]);
        $empleado->especialidades()->attach($elemental->id);

        $this->actingAs($admin)
            ->withSession(activeOfertaSession($oferta))
            ->patch(route('Admin.empleados.update', $empleado), empleadoPayload($pais, $provincia, $tipoContrato, [
                'roles' => [Role::Docente->value],
                'asignaturas' => [$media->id],
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertRedirect(route('Admin.empleados'));

        expect($empleado->especialidades()->pluck('sys_asignaturas.id')->all())->toBe([$media->id]);
    });

    it('returns 404 when editing an empleado from another establishment', function () {
        $admin = adminOf(Establecimiento::factory()->create());
        $empleado = Empleado::factory()->create();

        $this->actingAs($admin)
            ->get(route('Admin.empleados.edit', $empleado))
            ->assertNotFound();
    });
});

describe('destroy', function () {
    it('deletes the empleado, persona and user without deleting catalogs', function () {
        $establecimiento = Establecimiento::factory()->create();
        $admin = adminOf($establecimiento);
        $tipoContrato = Sys_TipoContrato::factory()->create();
        $funcion = Sys_Funcion::factory()->create();
        $user = assignRole(User::factory()->create([
            'establecimiento_id' => $establecimiento->id,
        ]), Role::Empleado);
        $persona = Persona::factory()->create([
            'user_id' => $user->id,
            'establecimiento_id' => $establecimiento->id,
        ]);
        $empleado = Empleado::factory()->for($persona)->create([
            'tipo_contrato_id' => $tipoContrato->id,
            'funcion_id' => $funcion->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('Admin.empleados.destroy', $empleado))
            ->assertRedirect(route('Admin.empleados'))
            ->assertSessionHas('status', 'empleado-deleted');

        $this->assertDatabaseMissing('empleados', ['id' => $empleado->id]);
        $this->assertDatabaseMissing('personas', ['id' => $persona->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('sys_tipo_contratos', ['id' => $tipoContrato->id]);
        $this->assertDatabaseHas('sys_funciones', ['id' => $funcion->id]);
    });

    it('returns 404 when deleting an empleado from another establishment', function () {
        $admin = adminOf(Establecimiento::factory()->create());
        $empleado = Empleado::factory()->create();

        $this->actingAs($admin)
            ->delete(route('Admin.empleados.destroy', $empleado))
            ->assertNotFound();

        $this->assertDatabaseHas('empleados', ['id' => $empleado->id]);
    });
});
