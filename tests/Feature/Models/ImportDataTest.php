<?php

use App\Models\Establecimiento;
use App\Models\ImportData;
use App\Models\ImportDataDetalle;

it('belongs to an establishment', function () {
    $establecimiento = Establecimiento::factory()->create();
    $import = ImportData::factory()->for($establecimiento)->create();

    expect($import->establecimiento->is($establecimiento))->toBeTrue();
});

it('has detalles', function () {
    $import = ImportData::factory()->create();
    $detalle = ImportDataDetalle::factory()->for($import, 'importData')->create();

    expect($import->detalles)->toHaveCount(1);
    expect($import->detalles->first()->is($detalle))->toBeTrue();
});
