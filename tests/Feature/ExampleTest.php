<?php

use App\Models\User;

it('renders the Educa home page for guests', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSee('Educa')
        ->assertSee('La vida escolar')
        ->assertSee('más conectada.')
        ->assertSee('Descubrir la plataforma')
        ->assertDontSee('Empieza con Educa')
        ->assertDontSee('Crear cuenta')
        ->assertDontSee('Crear una cuenta')
        ->assertSee('Iniciar sesión')
        ->assertDontSee(route('register'), false);
});

it('links authenticated users to the dashboard from the home page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response
        ->assertSee('Ir al panel')
        ->assertDontSee('Iniciar sesión');
});
