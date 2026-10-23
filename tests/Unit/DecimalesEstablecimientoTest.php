<?php

use App\Enums\ModoDecimal;
use App\Models\Establecimiento;
use App\Services\DecimalesEstablecimiento;
use Tests\TestCase;

uses(TestCase::class);

it('approximates or truncates a grade to the school decimals', function (ModoDecimal $modo, float $valor, int $decimales, float $esperado, string $texto) {
    $servicio = new DecimalesEstablecimiento;
    $establecimiento = new Establecimiento([
        'decimales_calificacion' => $decimales,
        'modo_decimal' => $modo,
    ]);

    expect($servicio->aplicar($valor, $establecimiento))->toBe($esperado)
        ->and($servicio->formatear($valor, $establecimiento))->toBe($texto);
})->with([
    'approximate 5.256 to two decimals' => [ModoDecimal::Aproximar, 5.256, 2, 5.26, '5.26'],
    'truncate 5.256 to two decimals' => [ModoDecimal::Truncar, 5.256, 2, 5.25, '5.25'],
    'approximate 5.251 stays 5.25' => [ModoDecimal::Aproximar, 5.251, 2, 5.25, '5.25'],
    'truncate 5.999 to two decimals' => [ModoDecimal::Truncar, 5.999, 2, 5.99, '5.99'],
    'approximate pond 5.257 to two decimals' => [ModoDecimal::Aproximar, 5.257, 2, 5.26, '5.26'],
    'truncate pond 5.257 to two decimals' => [ModoDecimal::Truncar, 5.257, 2, 5.25, '5.25'],
    'approximate half to integer' => [ModoDecimal::Aproximar, 8.5, 0, 9.0, '9'],
    'truncate half to integer' => [ModoDecimal::Truncar, 8.5, 0, 8.0, '8'],
]);

it('falls back to two decimals and approximation without a school', function () {
    $servicio = new DecimalesEstablecimiento;

    expect($servicio->aplicar(5.256, null))->toBe(5.26)
        ->and($servicio->formatear(5.256, null))->toBe('5.26')
        ->and($servicio->decimales(null))->toBe(2)
        ->and($servicio->modo(null))->toBe(ModoDecimal::Aproximar);
});
