<?php

use App\Models\User;

it('renders the Educa home page for guests', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSee('Educa')
        ->assertSee('El ecosistema educativo inteligente que se adapta a tu institución')
        ->assertSee('Una plataforma, infinitas posibilidades')
        ->assertSee('Cualquier modalidad')
        ->assertSee('Todas las jornadas')
        ->assertSee('Flexibilidad temporal')
        ->assertSee('Iniciar sesión')
        ->assertSee('Registrarse');
});

it('links authenticated users to the dashboard from the home page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response
        ->assertSee('Ir al panel')
        ->assertDontSee('Iniciar sesión');
});
