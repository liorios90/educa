<?php

use App\Rules\AsistenteBaseUrl;

it('accepts public and local assistant urls', function (string $url) {
    expect((new AsistenteBaseUrl(fn (): array => ['8.8.8.8']))->mensaje($url))->toBeNull();
})->with([
    'groq' => 'https://api.groq.com/openai/v1',
    'loopback' => 'http://127.0.0.1:11434/v1',
    'localhost' => 'http://localhost:11434/v1',
    'ipv6 loopback' => 'http://[::1]:11434/v1',
]);

it('rejects an assistant url that is not safe to call', function (string $url, string $mensaje) {
    expect((new AsistenteBaseUrl(fn (): array => ['10.0.0.1']))->mensaje($url))->toBe($mensaje);
})->with([
    'link local over http' => ['http://169.254.169.254', 'La URL base debe usar https, salvo en localhost.'],
    'link local over https' => ['https://169.254.169.254/latest', 'La URL base no puede apuntar a una red privada.'],
    'private address' => ['https://10.0.0.1/v1', 'La URL base no puede apuntar a una red privada.'],
    'http hostname' => ['http://api.groq.com/openai/v1', 'La URL base debe usar https, salvo en localhost.'],
    'user info' => ['https://user:pass@api.groq.com/v1', 'La URL base no puede incluir usuario ni contraseña.'],
    'name that resolves privately' => ['https://interno.example/v1', 'La URL base no puede apuntar a una red privada.'],
]);

it('rejects an assistant url when the host cannot be resolved', function () {
    expect((new AsistenteBaseUrl(fn (): array => []))->mensaje('https://modelo.example/v1'))
        ->toBe('No se pudo comprobar el servidor de la URL base.');
});

it('ignores an empty assistant url', function () {
    expect((new AsistenteBaseUrl)->mensaje(''))->toBeNull();
});
