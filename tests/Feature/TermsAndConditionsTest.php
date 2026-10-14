<?php

it('renders the terms and conditions for guests', function () {
    $response = $this->get(route('terms'));

    $response
        ->assertSee('Términos y condiciones')
        ->assertSee('Cuentas y acceso')
        ->assertSee('Uso aceptable')
        ->assertSee('Ley aplicable');
});

it('is linked from the home page', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSee('Términos y condiciones')
        ->assertSee(route('terms'), false);
});

it('is linked from the registration page', function () {
    $response = $this->get(route('register'));

    $response
        ->assertSee('términos y condiciones')
        ->assertSee(route('terms'), false);
});
