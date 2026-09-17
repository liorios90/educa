<?php

use App\Enums\Role;
use App\Models\Establecimiento;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('redirects guests from the dashboard to login', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

it('shows the establishment name and logo on inicio for administrators', function () {
    Storage::fake('public');
    Storage::disk('public')->put('establecimientos/logos/dm2.jpeg', 'sistemas-logo');
    Storage::disk('public')->put('establecimientos/logos/ue-andes.png', 'logo-content');

    $establecimiento = Establecimiento::factory()->create([
        'nombre' => 'UE Los Andes',
        'codigo_amie' => '17H00001',
        'regimen' => 'Sierra',
        'logo' => 'establecimientos/logos/ue-andes.png',
    ]);
    $user = assignRole(User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Admin);

    $response = $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('UE Los Andes')
        ->assertSee('Código AMIE 17H00001')
        ->assertSee('Régimen Sierra')
        ->assertSee('/storage/establecimientos/logos/ue-andes.png', false)
        ->assertSee('alt="Logo de UE Los Andes"', false)
        ->assertDontSee('/storage/establecimientos/logos/dm2.jpeg', false);

    expect(substr_count($response->getContent(), '/storage/establecimientos/logos/ue-andes.png'))->toBe(1);
});

it('shows a monogram when the administrator establishment has no logo file', function () {
    Storage::fake('public');

    $establecimiento = Establecimiento::factory()->create([
        'nombre' => 'Unidad Educativa Cotopaxi',
        'logo' => 'establecimientos/logos/faltante.png',
    ]);
    $user = assignRole(User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Admin);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Unidad Educativa Cotopaxi')
        ->assertSee('UE')
        ->assertDontSee('/storage/establecimientos/logos/faltante.png', false);
});

it('escapes the establishment name on inicio', function () {
    Storage::fake('public');

    $establecimiento = Establecimiento::factory()->create([
        'nombre' => "<script>alert('xss')</script>",
        'logo' => 'establecimientos/logos/faltante.png',
    ]);
    $user = assignRole(User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Admin);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee("<script>alert('xss')</script>")
        ->assertDontSee("<script>alert('xss')</script>", false);
});

it('does not show another establishment on inicio for systems users', function () {
    Establecimiento::factory()->create(['nombre' => 'UE Los Andes']);
    $user = assignRole(User::factory()->create(), Role::Sistemas);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Sistemas')
        ->assertDontSee('UE Los Andes');
});

it('shows the sistemas logo on inicio for systems users', function () {
    Storage::fake('public');
    Storage::disk('public')->put('establecimientos/logos/dm2.jpeg', 'logo-content');
    Storage::disk('public')->put('establecimientos/logos/ue-andes.png', 'school-logo');
    $establecimiento = Establecimiento::factory()->create([
        'nombre' => 'UE Los Andes',
        'logo' => 'establecimientos/logos/ue-andes.png',
    ]);
    $user = assignRole(User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Sistemas);

    $response = $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('/storage/establecimientos/logos/dm2.jpeg', false)
        ->assertSee('alt="Logo de Sistemas"', false)
        ->assertSee('Sistemas')
        ->assertDontSee('UE Los Andes')
        ->assertDontSee('/storage/establecimientos/logos/ue-andes.png', false);

    expect(substr_count($response->getContent(), '/storage/establecimientos/logos/dm2.jpeg'))->toBe(1);
});

it('shows the establishment logo on inicio for secretaries', function () {
    Storage::fake('public');
    Storage::disk('public')->put('establecimientos/logos/dm2.jpeg', 'sistemas-logo');
    Storage::disk('public')->put('establecimientos/logos/ue-andes.png', 'school-logo');

    $establecimiento = Establecimiento::factory()->create([
        'nombre' => 'UE Los Andes',
        'logo' => 'establecimientos/logos/ue-andes.png',
    ]);
    $user = assignRole(User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
    ]), Role::Secretaria);

    $response = $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('UE Los Andes')
        ->assertSee('/storage/establecimientos/logos/ue-andes.png', false)
        ->assertDontSee('/storage/establecimientos/logos/dm2.jpeg', false)
        ->assertDontSee('alt="Logo de Sistemas"', false);

    expect(substr_count($response->getContent(), '/storage/establecimientos/logos/ue-andes.png'))->toBe(1);
});

it('shows only the logo of the active role for a multi-role user', function (Role $activeRole, string $visibleLogo, string $hiddenLogo, string $hiddenText) {
    Storage::fake('public');
    Storage::disk('public')->put('establecimientos/logos/dm2.jpeg', 'sistemas-logo');
    Storage::disk('public')->put('establecimientos/logos/ue-andes.png', 'school-logo');

    $establecimiento = Establecimiento::factory()->create([
        'nombre' => 'UE Los Andes',
        'logo' => 'establecimientos/logos/ue-andes.png',
    ]);
    $user = User::factory()->create([
        'establecimiento_id' => $establecimiento->id,
    ]);
    assignRole($user, Role::Admin);
    assignRole($user, Role::Sistemas);

    $this->actingAs($user)
        ->post(route('role.store'), ['role' => $activeRole->value])
        ->assertRedirect();

    $response = $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($visibleLogo, false)
        ->assertDontSee($hiddenLogo, false)
        ->assertDontSee($hiddenText);

    expect(substr_count($response->getContent(), $visibleLogo))->toBe(1);
})->with([
    'sistemas' => [Role::Sistemas, '/storage/establecimientos/logos/dm2.jpeg', '/storage/establecimientos/logos/ue-andes.png', 'UE Los Andes'],
    'administrador' => [Role::Admin, '/storage/establecimientos/logos/ue-andes.png', '/storage/establecimientos/logos/dm2.jpeg', 'Logo de Sistemas'],
]);
