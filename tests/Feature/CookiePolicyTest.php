<?php

it('renders the cookie policy for guests', function () {
    $response = $this->get(route('cookies'));

    $response
        ->assertSee('Política de cookies')
        ->assertSee('¿Hace falta tu consentimiento?')
        ->assertSee('Cookies que usamos')
        ->assertSee('XSRF-TOKEN');
});

it('is linked from the home page', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSee('Política de cookies')
        ->assertSee(route('cookies'), false);
});

it('is linked from the privacy policy', function () {
    $response = $this->get(route('privacy'));

    $response
        ->assertSee('política de cookies')
        ->assertSee(route('cookies'), false);
});
