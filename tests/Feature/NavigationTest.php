<?php

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\NavigationItem;
use App\Models\User;
use App\Navigation\Navigation;
use Database\Seeders\NavigationSeeder;

it('shows shared links and hides role links for users without a role', function () {
    $this->seed(NavigationSeeder::class);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('Inicio')
        ->assertSee('Reportes')
        ->assertDontSee('Usuarios')
        ->assertDontSee(route('Admin.padres'), false)
        ->assertDontSee(route('sistemas.home'), false);

    expect(collect((new Navigation)->itemsFor($user))->pluck('label')->all())
        ->toContain('Inicio', 'Reportes')
        ->not->toContain('Perfil');
});

it('shows administrator menu links and hides systems links', function () {
    $this->seed(NavigationSeeder::class);

    $user = assignRole(User::factory()->create(), Role::Admin);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('Matenimiento')
        ->assertSee(route('Admin.padres'), false)
        ->assertDontSee(route('sistemas.home'), false);
});

it('shows the systems link only to systems users', function () {
    $this->seed(NavigationSeeder::class);

    $user = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee(route('sistemas.home'), false)
        ->assertDontSee(route('Admin.padres'), false);
});

it('redirects a multi-role user to choose a role before the dashboard', function () {
    $this->seed(NavigationSeeder::class);

    $user = User::factory()->create();
    assignRole($user, Role::Admin);
    assignRole($user, Role::Sistemas);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('role.select'));
});

it('allows administrators to open the parents page', function () {
    $user = adminOf(Establecimiento::factory()->create());

    $this->actingAs($user)
        ->get(route('Admin.padres'))
        ->assertSee('Padres de familia');
});

it('forbids systems users from opening the parents page', function () {
    $user = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($user)
        ->get(route('Admin.padres'))
        ->assertForbidden();
});

it('allows systems users to open the systems page', function () {
    $user = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($user)
        ->get(route('sistemas.home'))
        ->assertSee('Herramientas técnicas reservadas al rol Sistemas');
});

it('forbids administrators from opening the systems page', function () {
    $user = assignRole(User::factory()->create(), Role::Admin);

    $this->actingAs($user)
        ->get(route('sistemas.home'))
        ->assertForbidden();
});

it('redirects guests from role pages to login', function (string $routeName) {
    $this->get(route($routeName))
        ->assertRedirect(route('login'));
})->with([
    'parents page' => 'Admin.padres',
    'systems page' => 'sistemas.home',
]);

it('escapes the user name in the sidebar', function () {
    $user = User::factory()->create([
        'name' => "<script>alert('xss')</script>",
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee("<script>alert('xss')</script>")
        ->assertDontSee("<script>alert('xss')</script>", false);
});

it('opens the dashboard when a menu link needs a route parameter', function () {
    NavigationItem::query()->create([
        'label' => 'Respuestas de matrícula',
        'route_name' => 'Admin.matriculas.submissions',
        'icon' => 'clipboard-document-list',
        'sort_order' => 5,
        'is_active' => true,
        'visible_to_all' => true,
        'is_group' => false,
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Respuestas de matrícula');
});

it('renders controls to open and close the sidebar', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('Cerrar menú')
        ->assertSee('Abrir menú');
});

it('returns only the links the user may see', function () {
    $this->seed(NavigationSeeder::class);

    $admin = assignRole(User::factory()->create(), Role::Admin);

    $items = collect((new Navigation)->itemsFor($admin));
    $configuracion = $items->firstWhere('label', 'Configuración');

    $matriculas = $items->firstWhere('label', 'Matriculas');

    expect($items->pluck('label')->all())->toContain('Matenimiento', 'Estructura Institucional', 'Configuración', 'Matriculas', 'Grupos flotantes')
        ->not->toContain('Grupos de clase', 'Usuarios')
        ->and(collect($configuracion?->children)->pluck('label')->all())->toBe(['Período', 'Por grados', 'Por Asignaturas', 'Tipos de Insumos', 'Tablas de Equivalencias', 'Asistente'])
        ->and(collect($matriculas?->children)->pluck('label')->all())->toBe(['Formularios', 'Matricular Alumno']);
});

it('shows class groups and programs to secretaries', function () {
    $this->seed(NavigationSeeder::class);

    $secretaria = secretariaOf(Establecimiento::factory()->create());

    $this->actingAs($secretaria)
        ->get(route('dashboard'))
        ->assertSee(route('Admin.grupos-asignatura.index'), false)
        ->assertSee(route('Admin.grupos-asignatura.flotantes'), false)
        ->assertSee(route('Admin.matriculas.index'), false)
        ->assertSee(route('secretaria.asistente'), false)
        ->assertDontSee(route('Admin.aulas'), false)
        ->assertDontSee(route('Admin.programas.index'), false);

    expect(collect((new Navigation)->itemsFor($secretaria))->pluck('label')->all())
        ->toContain('Matriculas', 'Grupos flotantes', 'Asistente')
        ->not->toContain('Grupos de clase', 'Programas');
});

it('does not overwrite a menu item that was customized in sistemas', function () {
    $this->seed(NavigationSeeder::class);

    $horario = NavigationItem::query()->where('route_name', 'Docente.horarios')->firstOrFail();
    $horario->update([
        'label' => 'Mi horario',
        'icon' => 'home',
        'sort_order' => 99,
    ]);
    $horario->roles()->sync([]);

    $this->seed(NavigationSeeder::class);

    $horario->refresh();

    expect($horario->label)->toBe('Mi horario')
        ->and($horario->icon)->toBe('home')
        ->and($horario->sort_order)->toBe(99)
        ->and($horario->roles)->toHaveCount(0)
        ->and(NavigationItem::query()->where('route_name', 'Docente.horarios')->count())->toBe(1);
});

it('creates only a missing menu item on a later seed', function () {
    $this->seed(NavigationSeeder::class);

    $count = NavigationItem::query()->count();
    NavigationItem::query()->where('route_name', 'secretaria.asistente')->delete();

    $this->seed(NavigationSeeder::class);

    $this->assertDatabaseHas('navigation_items', [
        'route_name' => 'secretaria.asistente',
        'label' => 'Asistente',
    ]);
    expect(NavigationItem::query()->count())->toBe($count);
});
