<?php

use App\Enums\EscalaCalificacion;
use App\Enums\TipoPrograma;
use App\Services\ConsultaDocumentosAcademicos;
use App\Services\OrganizacionAcademica;
use Illuminate\Validation\ValidationException;

it('keeps the home paralelo and the subject grade when the class mixes classrooms', function () {
    $curso = cursoDeOctavo();
    $organizacion = app(OrganizacionAcademica::class);
    $consulta = app(ConsultaDocumentosAcademicos::class);
    $delParaleloA = alumnoDelEstablecimiento($curso['establecimiento'], 'Aldaz');
    $delParaleloB = alumnoDelEstablecimiento($curso['establecimiento'], 'Benítez');
    $organizacion->matricular($curso['aulaA'], $delParaleloA);
    $organizacion->matricular($curso['aulaB'], $delParaleloB);
    $grupo = $organizacion->crearGrupo($curso['periodo'], $curso['educacionFisica'], 'Grupo 2');
    $organizacion->sincronizarAlumnosGrupo($grupo, [$delParaleloA->id, $delParaleloB->id]);
    $organizacion->registrarCalificacion($delParaleloB, $curso['periodo'], $curso['educacionFisica'], 1, 0, 8.75, $grupo);
    $organizacion->registrarCalificacion($delParaleloB, $curso['periodo'], $curso['educacionFisica'], 1, 1, 7.5, $grupo);
    $organizacion->registrarCalificacion($delParaleloB, $curso['periodo'], $curso['matematica'], 1, 0, 9.5);

    $libreta = $consulta->libretaQuimestral($delParaleloB, $curso['periodo'], 1);
    $parcial = $consulta->informeParcial($delParaleloB, $curso['periodo'], 1, 1);
    $certificado = $consulta->certificadoPromocion($delParaleloB, $curso['periodo']);

    expect($libreta->paralelo)->toBe('B')
        ->and($libreta->nombres())->toBe(['Educación Física', 'Matemática'])
        ->and($libreta->fila('Educación Física')?->notaDe(1, 0))->toBe('8.75')
        ->and($parcial->fila('Educación Física')?->notaDe(1, 1))->toBe('7.50')
        ->and($certificado->fila('Matemática')?->notaDe(1, 0))->toBe('9.50')
        ->and($certificado->fila('Educación Física')?->notaDe(1, 1))->toBeNull();
});

it('lists a trunk subject even when the student is not in the floating group', function () {
    $curso = cursoDeOctavo();
    $organizacion = app(OrganizacionAcademica::class);
    $alumno = alumnoDelEstablecimiento($curso['establecimiento'], 'Cevallos');
    $organizacion->matricular($curso['aulaA'], $alumno);
    $organizacion->crearGrupo($curso['periodo'], $curso['educacionFisica'], 'Grupo 1');

    $libreta = app(ConsultaDocumentosAcademicos::class)->libretaQuimestral($alumno, $curso['periodo'], 1);

    expect($libreta->nombres())->toContain('Educación Física')
        ->and($libreta->fila('Educación Física')?->notas)->toBe([]);
});

it('includes an optional subject only for the student who takes it', function () {
    $curso = cursoDeOctavo();
    $organizacion = app(OrganizacionAcademica::class);
    $inscrito = alumnoDelEstablecimiento($curso['establecimiento'], 'Dávalos');
    $companero = alumnoDelEstablecimiento($curso['establecimiento'], 'Espinoza');
    $organizacion->matricular($curso['aulaA'], $inscrito);
    $organizacion->matricular($curso['aulaA'], $companero);
    $organizacion->sincronizarInscripciones($curso['periodo'], $curso['optativa'], [$inscrito->id]);

    $consulta = app(ConsultaDocumentosAcademicos::class);

    expect($consulta->certificadoPromocion($inscrito, $curso['periodo'])->nombres())->toContain('Emprendimiento y Gestión')
        ->and($consulta->certificadoPromocion($companero, $curso['periodo'])->nombres())->not->toContain('Emprendimiento y Gestión');
});

it('keeps international baccalaureate grades out of the national certificate', function () {
    $curso = cursoDeOctavo();
    $organizacion = app(OrganizacionAcademica::class);
    $consulta = app(ConsultaDocumentosAcademicos::class);
    $delPrograma = alumnoDelEstablecimiento($curso['establecimiento'], 'Flores');
    $companero = alumnoDelEstablecimiento($curso['establecimiento'], 'Guerra');
    $organizacion->matricular($curso['aulaA'], $delPrograma);
    $organizacion->matricular($curso['aulaB'], $companero);
    $programa = $organizacion->crearPrograma($curso['periodo'], TipoPrograma::BachilleratoInternacional, 'Bachillerato Internacional');
    $organizacion->sincronizarAlumnosPrograma($programa, [$delPrograma->id]);
    $organizacion->sincronizarAsignaturasPrograma($programa, [$curso['bachillerato']->id]);
    $organizacion->sincronizarInscripciones($curso['periodo'], $curso['bachillerato']->fresh(), [$delPrograma->id]);
    $organizacion->registrarCalificacion($delPrograma, $curso['periodo'], $curso['bachillerato']->fresh(), 1, 0, 6);
    $organizacion->registrarCalificacion($delPrograma, $curso['periodo'], $curso['matematica'], 1, 0, 9.25);

    $certificado = $consulta->certificadoPromocion($delPrograma, $curso['periodo']);
    $informe = $consulta->informePrograma($delPrograma, $programa);
    $companeroCertificado = $consulta->certificadoPromocion($companero, $curso['periodo']);

    expect($certificado->nombres())->toBe(['Educación Física', 'Matemática'])
        ->and($certificado->fila('Matemática')?->notaDe(1, 0))->toBe('9.25')
        ->and($certificado->fila('Matemática')?->escala)->toBe(EscalaCalificacion::Nacional)
        ->and($informe->nombres())->toBe(['Matemáticas AA NS'])
        ->and($informe->fila('Matemáticas AA NS')?->notaDe(1, 0))->toBe('6.00')
        ->and($informe->fila('Matemáticas AA NS')?->escala)->toBe(EscalaCalificacion::BachilleratoInternacional)
        ->and($companeroCertificado->nombres())->not->toContain('Matemáticas AA NS');

    expect(fn () => $consulta->informePrograma($companero, $programa))
        ->toThrow(ValidationException::class);
});
