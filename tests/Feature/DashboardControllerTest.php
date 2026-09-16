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

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('UE Los Andes')
        ->assertSee('Código AMIE 17H00001')
        ->assertSee('Régimen Sierra')
        ->assertSee('/storage/establecimientos/logos/ue-andes.png', false)
        ->assertSee('alt="Logo de UE Los Andes"', false);
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
        ->assertSee('Bienvenido al panel')
        ->assertDontSee('UE Los Andes');
});
