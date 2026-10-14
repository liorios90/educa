<?php

it('renders the privacy policy for guests', function () {
    $response = $this->get(route('privacy'));

    $response
        ->assertSee('Política de privacidad')
        ->assertSee('Ley Orgánica de Protección de Datos Personales')
        ->assertSee('Datos de niñas, niños y adolescentes')
        ->assertSee('Tus derechos');
});

it('is linked from the home page', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSee('Política de privacidad')
        ->assertSee(route('privacy'), false);
});

it('is linked from the registration page', function () {
    $response = $this->get(route('register'));

    $response
        ->assertSee('política de privacidad')
        ->assertSee(route('privacy'), false);
});
