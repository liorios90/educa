<?php

use Illuminate\Support\Facades\Schema;

it('replaces the attendance unique index with aula and subject key', function () {
    expect(Schema::hasTable('establecimiento_asistencias'))->toBeTrue()
        ->and(Schema::hasColumns('establecimiento_asistencias', [
            'alumno_id',
            'establecimiento_asignatura_id',
            'clave_asignatura',
            'establecimiento_aula_id',
            'fecha',
            'alcance',
        ]))->toBeTrue();

    $indices = collect(Schema::getIndexes('establecimiento_asistencias'));

    expect($indices->contains(
        fn (array $index): bool => $index['name'] === 'est_asistencias_alumno_asignatura_fecha_unq',
    ))->toBeFalse();

    $unico = $indices->first(
        fn (array $index): bool => $index['name'] === 'est_asistencias_alumno_aula_fecha_clave_unq',
    );

    expect($unico)->not->toBeNull()
        ->and($unico['unique'])->toBeTrue()
        ->and($unico['columns'])->toBe([
            'alumno_id',
            'establecimiento_aula_id',
            'fecha',
            'clave_asignatura',
        ]);
});
