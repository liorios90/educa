<?php

use App\Enums\EscalaCalificacion;
use App\Enums\TipoAsignatura;
use App\Enums\TipoCalificacion;
use App\Enums\TipoPrograma;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoGrupoAlumno;
use App\Models\EstablecimientoMatricula;
use App\Services\OrganizacionAcademica;
use App\Services\SyncEstablecimientoAsignaturas;
use Illuminate\Validation\ValidationException;

it('assigns a unique sequential enrollment number that is not reused', function () {
    $curso = cursoDeOctavo();
    $organizacion = app(OrganizacionAcademica::class);
    $primero = $organizacion->matricular($curso['aulaA'], alumnoDelEstablecimiento($curso['establecimiento'], 'Uno'), '2026-10-16');
    $segundo = $organizacion->matricular($curso['aulaA'], alumnoDelEstablecimiento($curso['establecimiento'], 'Dos'), '2026-10-16');

    expect($primero->numero)->toBe(sprintf('E%d-%d-0001', $curso['establecimiento']->id, $curso['periodo']->id))
        ->and($segundo->numero)->toBe(sprintf('E%d-%d-0002', $curso['establecimiento']->id, $curso['periodo']->id))
        ->and($primero->fecha?->toDateString())->toBe('2026-10-16');

    $organizacion->desmatricular($primero);

    $tercero = $organizacion->matricular($curso['aulaA'], alumnoDelEstablecimiento($curso['establecimiento'], 'Tres'), '2026-10-16');

    expect($tercero->numero)->toBe(sprintf('E%d-%d-0003', $curso['establecimiento']->id, $curso['periodo']->id))
        ->and(EstablecimientoMatricula::query()->where('numero', $primero->numero)->exists())->toBeFalse();

    $organizacion->desmatricular($segundo);
    $organizacion->desmatricular($tercero);

    $cuarto = $organizacion->matricular($curso['aulaA'], alumnoDelEstablecimiento($curso['establecimiento'], 'Cuatro'), '2026-10-16');

    expect($cuarto->numero)->toBe(sprintf('E%d-%d-0004', $curso['establecimiento']->id, $curso['periodo']->id));
});

it('rejects a second enrollment in the same period', function () {
    $curso = cursoDeOctavo();
    $alumno = alumnoDelEstablecimiento($curso['establecimiento'], 'Aldaz');
    $organizacion = app(OrganizacionAcademica::class);
    $organizacion->matricular($curso['aulaA'], $alumno);

    expect(fn () => $organizacion->matricular($curso['aulaB'], $alumno))
        ->toThrow(fn (ValidationException $exception) => expect($exception->errors()['alumno_id'][0])->toBe('El alumno ya está matriculado en un paralelo de este periodo.'));
});

it('removes group membership when a student leaves the classroom', function () {
    $curso = cursoDeOctavo();
    $organizacion = app(OrganizacionAcademica::class);
    $alumno = alumnoDelEstablecimiento($curso['establecimiento'], 'Aldaz');
    $matricula = $organizacion->matricular($curso['aulaA'], $alumno);
    $grupo = $organizacion->crearGrupo($curso['periodo'], $curso['educacionFisica'], 'Grupo 1');
    $organizacion->sincronizarAlumnosGrupo($grupo, [$alumno->id]);

    $organizacion->desmatricular($matricula);

    expect(EstablecimientoMatricula::query()->where('id', $matricula->id)->exists())->toBeFalse();
    expect(EstablecimientoGrupoAlumno::query()->where('alumno_id', $alumno->id)->exists())->toBeFalse();
});

it('rejects a student from another grade in a floating group', function () {
    $curso = cursoDeOctavo();
    ['grado' => $otroCatalogo] = catalogoEstructura([
        'nivel' => 'Educación General Básica',
        'subnivel' => 'Básica Superior',
        'grado' => 'Noveno de básica',
    ]);
    $otroGrado = EstablecimientoGrado::factory()->create([
        'establecimiento_id' => $curso['establecimiento']->id,
        'establecimiento_modalidad_jornada_id' => $curso['oferta']->id,
        'subnivel_id' => $otroCatalogo->subnivel_id,
        'grado_id' => $otroCatalogo->id,
    ]);
    $aulaNoveno = EstablecimientoAula::factory()->create([
        'establecimiento_id' => $curso['establecimiento']->id,
        'establecimiento_modalidad_jornada_id' => $curso['oferta']->id,
        'establecimiento_periodo_id' => $curso['periodo']->id,
        'establecimiento_grado_id' => $otroGrado->id,
        'paralelo' => 'A',
    ]);
    $organizacion = app(OrganizacionAcademica::class);
    $alumno = alumnoDelEstablecimiento($curso['establecimiento'], 'Noveno');
    $organizacion->matricular($aulaNoveno, $alumno);
    $grupo = $organizacion->crearGrupo($curso['periodo'], $curso['educacionFisica'], 'Grupo 1');

    expect(fn () => $organizacion->sincronizarAlumnosGrupo($grupo, [$alumno->id]))
        ->toThrow(fn (ValidationException $exception) => expect($exception->errors()['alumno_id'][0])->toBe('La asignatura no corresponde al grado en el que está matriculado el alumno.'));
});

it('rejects the same student in two groups of one subject', function () {
    $curso = cursoDeOctavo();
    $organizacion = app(OrganizacionAcademica::class);
    $alumno = alumnoDelEstablecimiento($curso['establecimiento'], 'Aldaz');
    $organizacion->matricular($curso['aulaA'], $alumno);
    $primero = $organizacion->crearGrupo($curso['periodo'], $curso['educacionFisica'], 'Grupo 1');
    $segundo = $organizacion->crearGrupo($curso['periodo'], $curso['educacionFisica'], 'Grupo 2');
    $organizacion->sincronizarAlumnosGrupo($primero, [$alumno->id]);

    expect(fn () => $organizacion->sincronizarAlumnosGrupo($segundo, [$alumno->id]))
        ->toThrow(fn (ValidationException $exception) => expect($exception->errors()['alumno_id'][0])->toBe('El alumno ya pertenece a un grupo de esta asignatura.'));
});

it('rejects an international baccalaureate grade outside 1 to 7', function () {
    $curso = cursoDeOctavo();
    $organizacion = app(OrganizacionAcademica::class);
    $alumno = alumnoDelEstablecimiento($curso['establecimiento'], 'Flores');
    $organizacion->matricular($curso['aulaA'], $alumno);
    $programa = $organizacion->crearPrograma($curso['periodo'], TipoPrograma::BachilleratoInternacional, 'Bachillerato Internacional');
    $organizacion->sincronizarAlumnosPrograma($programa, [$alumno->id]);
    $organizacion->sincronizarAsignaturasPrograma($programa, [$curso['bachillerato']->id]);
    $asignatura = $curso['bachillerato']->fresh();
    $organizacion->sincronizarInscripciones($curso['periodo'], $asignatura, [$alumno->id]);

    expect(fn () => $organizacion->registrarCalificacion($alumno, $curso['periodo'], $asignatura, 1, 0, 9))
        ->toThrow(fn (ValidationException $exception) => expect($exception->errors()['nota'][0])->toBe('La nota del Bachillerato Internacional va de 1 a 7.'));
});

it('keeps the floating flag and the same subject when the malla is saved again', function () {
    $curso = cursoDeOctavo();
    $organizacion = app(OrganizacionAcademica::class);
    $grupo = $organizacion->crearGrupo($curso['periodo'], $curso['educacionFisica'], 'Grupo 1');
    $curso['educacionFisica']->update([
        'escala' => EscalaCalificacion::Nacional,
        'es_flotante' => true,
    ]);

    app(SyncEstablecimientoAsignaturas::class)->handle(
        $curso['establecimiento'],
        $curso['oferta'],
        [[
            'asignatura_id' => $curso['educacionFisica']->asignatura_id,
            'asignatura_propia_clave' => null,
            'area_id' => 1,
            'grado_id' => $curso['catalogo']->id,
            'tipo_asignatura' => TipoAsignatura::TroncoComun,
            'aparece_en_libreta' => true,
            'horas_catalogo' => 2,
            'tipo_calificacion' => TipoCalificacion::Cuantitativa,
            'califica_por_destrezas' => false,
            'usa_tabla_equivalencias' => false,
            'porcentaje' => null,
            'origen' => 'catalogo',
        ]],
    );

    $asignatura = EstablecimientoAsignatura::query()->find($curso['educacionFisica']->id);

    expect($asignatura)->not->toBeNull()
        ->and($asignatura->es_flotante)->toBeTrue()
        ->and($grupo->fresh())->not->toBeNull();
});

it('does not use a national curriculum subject as an international baccalaureate subject', function () {
    $curso = cursoDeOctavo();
    $organizacion = app(OrganizacionAcademica::class);
    $programa = $organizacion->crearPrograma($curso['periodo'], TipoPrograma::BachilleratoInternacional, 'Bachillerato Internacional');

    expect(fn () => $organizacion->sincronizarAsignaturasPrograma($programa, [$curso['matematica']->id]))
        ->toThrow(fn (ValidationException $exception) => expect($exception->errors()['asignaturas'][0])->toBe('El Bachillerato Internacional usa asignaturas propias, no las del currículo nacional.'));
});
