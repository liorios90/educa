<?php

namespace App\Services;

use App\Enums\EscalaCalificacion;
use App\Enums\TipoAsignatura;
use App\Models\Alumno;
use App\Models\EstablecimientoAlumnoAsignatura;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoCalificacion;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoPrograma;
use App\Services\Documentos\FilaDocumentoAcademico;
use App\Services\Documentos\ResultadoDocumentoAcademico;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ConsultaDocumentosAcademicos
{
    public function libretaQuimestral(Alumno $alumno, EstablecimientoPeriodo $periodo, int $cicloOrden): ResultadoDocumentoAcademico
    {
        return $this->documentoNacional($alumno, $periodo, $cicloOrden, 0);
    }

    public function informeParcial(Alumno $alumno, EstablecimientoPeriodo $periodo, int $cicloOrden, int $parcialNumero): ResultadoDocumentoAcademico
    {
        return $this->documentoNacional($alumno, $periodo, $cicloOrden, $parcialNumero);
    }

    public function certificadoPromocion(Alumno $alumno, EstablecimientoPeriodo $periodo): ResultadoDocumentoAcademico
    {
        return $this->documentoNacional($alumno, $periodo, null, 0);
    }

    public function informePrograma(Alumno $alumno, EstablecimientoPrograma $programa): ResultadoDocumentoAcademico
    {
        $periodo = $this->periodoDe($programa);
        $matricula = $this->matriculaDe($alumno, $periodo);
        $this->exigirMiembro($programa, $alumno);

        $inscritas = $this->asignaturasInscritas($alumno, $periodo);
        $gradoId = $this->gradoId($matricula);

        $asignaturas = EstablecimientoAsignatura::query()
            ->with(['asignatura.area', 'asignaturaPropia.area'])
            ->where('establecimiento_programa_id', $programa->id)
            ->where('grado_id', $gradoId)
            ->whereIn('id', $inscritas)
            ->orderBy('id')
            ->get();

        return $this->resultado($matricula, $asignaturas, $periodo, null, null);
    }

    private function documentoNacional(Alumno $alumno, EstablecimientoPeriodo $periodo, ?int $cicloOrden, int $parcialNumero): ResultadoDocumentoAcademico
    {
        $matricula = $this->matriculaDe($alumno, $periodo);
        $matricula->loadMissing('aula');
        $inscritas = $this->asignaturasInscritas($alumno, $periodo);

        $asignaturas = EstablecimientoAsignatura::query()
            ->with(['asignatura.area', 'asignaturaPropia.area'])
            ->where('establecimiento_modalidad_jornada_id', $matricula->aula->establecimiento_modalidad_jornada_id)
            ->where('grado_id', $this->gradoId($matricula))
            ->where('aparece_en_libreta', true)
            ->where('escala', EscalaCalificacion::Nacional)
            ->where('tipo_asignatura', '!=', TipoAsignatura::ProgramaInternacional)
            ->orderBy('id')
            ->get()
            ->filter(function (EstablecimientoAsignatura $asignatura) use ($inscritas): bool {
                if (! $asignatura->tipo_asignatura->exigeInscripcionIndividual()) {
                    return true;
                }

                return in_array((int) $asignatura->id, $inscritas, true);
            })
            ->values();

        return $this->resultado($matricula, $asignaturas, $periodo, $cicloOrden, $parcialNumero);
    }

    /**
     * @param  Collection<int, EstablecimientoAsignatura>  $asignaturas
     */
    private function resultado(
        EstablecimientoMatricula $matricula,
        Collection $asignaturas,
        EstablecimientoPeriodo $periodo,
        ?int $cicloOrden,
        ?int $parcialNumero,
    ): ResultadoDocumentoAcademico {
        $matricula->loadMissing('aula.establecimientoGrado.grado');

        $notas = EstablecimientoCalificacion::query()
            ->where('establecimiento_periodo_id', $periodo->id)
            ->where('alumno_id', $matricula->alumno_id)
            ->whereIn('establecimiento_asignatura_id', $asignaturas->pluck('id'))
            ->when($cicloOrden !== null, fn ($query) => $query->where('ciclo_orden', $cicloOrden))
            ->when($parcialNumero !== null, fn ($query) => $query->where('parcial_numero', $parcialNumero))
            ->orderBy('ciclo_orden')
            ->orderBy('parcial_numero')
            ->get()
            ->groupBy('establecimiento_asignatura_id');

        $filas = $asignaturas
            ->map(function (EstablecimientoAsignatura $asignatura) use ($notas): FilaDocumentoAcademico {
                $notasDeLaFila = $notas->get($asignatura->id, collect())
                    ->map(fn (EstablecimientoCalificacion $nota): array => [
                        'ciclo_orden' => (int) $nota->ciclo_orden,
                        'parcial_numero' => (int) $nota->parcial_numero,
                        'nota' => (string) $nota->nota,
                    ])
                    ->values()
                    ->all();

                return new FilaDocumentoAcademico(
                    establecimientoAsignaturaId: (int) $asignatura->id,
                    nombre: $asignatura->nombre(),
                    area: $asignatura->areaNombre(),
                    tipoAsignatura: $asignatura->tipo_asignatura,
                    escala: $asignatura->escala,
                    notas: $notasDeLaFila,
                );
            })
            ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return new ResultadoDocumentoAcademico(
            matriculaId: (int) $matricula->id,
            aulaId: (int) $matricula->establecimiento_aula_id,
            paralelo: (string) $matricula->aula->paralelo,
            gradoId: $this->gradoId($matricula),
            grado: $matricula->aula->establecimientoGrado?->etiqueta() ?? '',
            filas: $filas,
        );
    }

    /**
     * @return list<int>
     */
    private function asignaturasInscritas(Alumno $alumno, EstablecimientoPeriodo $periodo): array
    {
        return EstablecimientoAlumnoAsignatura::query()
            ->where('alumno_id', $alumno->id)
            ->where('establecimiento_periodo_id', $periodo->id)
            ->pluck('establecimiento_asignatura_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    private function matriculaDe(Alumno $alumno, EstablecimientoPeriodo $periodo): EstablecimientoMatricula
    {
        $matricula = EstablecimientoMatricula::query()
            ->where('alumno_id', $alumno->id)
            ->where('establecimiento_periodo_id', $periodo->id)
            ->first();

        if ($matricula === null) {
            throw ValidationException::withMessages([
                'alumno_id' => 'El alumno no está matriculado en este periodo.',
            ]);
        }

        return $matricula;
    }

    private function gradoId(EstablecimientoMatricula $matricula): int
    {
        $matricula->loadMissing('aula.establecimientoGrado');

        return (int) $matricula->aula->establecimientoGrado?->grado_id;
    }

    private function periodoDe(EstablecimientoPrograma $programa): EstablecimientoPeriodo
    {
        $programa->loadMissing('periodo');

        return $programa->periodo;
    }

    private function exigirMiembro(EstablecimientoPrograma $programa, Alumno $alumno): void
    {
        $pertenece = $programa->membresias()
            ->where('alumno_id', $alumno->id)
            ->exists();

        if (! $pertenece) {
            throw ValidationException::withMessages([
                'alumno_id' => 'El alumno no pertenece a este programa.',
            ]);
        }
    }
}
