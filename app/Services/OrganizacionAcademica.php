<?php

namespace App\Services;

use App\Enums\EscalaCalificacion;
use App\Enums\TipoAsignatura;
use App\Enums\TipoCalificacion;
use App\Enums\TipoPrograma;
use App\Models\Alumno;
use App\Models\Empleado;
use App\Models\EstablecimientoAlumnoAsignatura;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoCalificacion;
use App\Models\EstablecimientoGrupo;
use App\Models\EstablecimientoGrupoAlumno;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\EstablecimientoPrograma;
use App\Models\EstablecimientoProgramaAlumno;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganizacionAcademica
{
    public function matricular(EstablecimientoAula $aula, Alumno $alumno, ?string $fecha = null): EstablecimientoMatricula
    {
        return DB::transaction(function () use ($aula, $alumno, $fecha): EstablecimientoMatricula {
            $this->exigirMismoEstablecimiento($aula, $alumno);

            $existente = EstablecimientoMatricula::query()
                ->where('alumno_id', $alumno->id)
                ->where('establecimiento_periodo_id', $aula->establecimiento_periodo_id)
                ->first();

            if ($existente !== null) {
                if ((int) $existente->establecimiento_aula_id === (int) $aula->id) {
                    return $existente;
                }

                throw ValidationException::withMessages([
                    'alumno_id' => 'El alumno ya está matriculado en un paralelo de este periodo.',
                ]);
            }

            $secuencia = $this->siguienteSecuencia(
                (int) $aula->establecimiento_id,
                (int) $aula->establecimiento_periodo_id,
            );

            return EstablecimientoMatricula::query()->create([
                'establecimiento_id' => $aula->establecimiento_id,
                'establecimiento_periodo_id' => $aula->establecimiento_periodo_id,
                'establecimiento_aula_id' => $aula->id,
                'alumno_id' => $alumno->id,
                'secuencia' => $secuencia,
                'numero' => $this->formatoNumeroMatricula(
                    (int) $aula->establecimiento_id,
                    (int) $aula->establecimiento_periodo_id,
                    $secuencia,
                ),
                'fecha' => $fecha ?? now()->toDateString(),
            ]);
        });
    }

    /**
     * @param  list<Alumno>  $alumnos
     */
    public function matricularVarios(EstablecimientoAula $aula, array $alumnos, ?string $fecha = null): int
    {
        return (int) DB::transaction(function () use ($aula, $alumnos, $fecha): int {
            $creadas = 0;

            foreach ($alumnos as $alumno) {
                $existente = EstablecimientoMatricula::query()
                    ->where('alumno_id', $alumno->id)
                    ->where('establecimiento_periodo_id', $aula->establecimiento_periodo_id)
                    ->exists();

                $this->matricular($aula, $alumno, $fecha);

                if (! $existente) {
                    $creadas++;
                }
            }

            return $creadas;
        });
    }

    public function siguienteNumeroMatricula(int $establecimientoId, int $periodoId): string
    {
        return $this->formatoNumeroMatricula(
            $establecimientoId,
            $periodoId,
            $this->ultimaSecuenciaMatricula($establecimientoId, $periodoId) + 1,
        );
    }

    public function formatoNumeroMatricula(int $establecimientoId, int $periodoId, int $secuencia): string
    {
        return sprintf('E%d-%d-%04d', $establecimientoId, $periodoId, $secuencia);
    }

    private function siguienteSecuencia(int $establecimientoId, int $periodoId): int
    {
        $periodo = EstablecimientoPeriodo::query()
            ->whereKey($periodoId)
            ->where('establecimiento_id', $establecimientoId)
            ->lockForUpdate()
            ->firstOrFail();

        $secuencia = $this->ultimaSecuenciaMatricula($establecimientoId, $periodoId, $periodo) + 1;

        $periodo->forceFill(['ultima_secuencia_matricula' => $secuencia])->save();

        return $secuencia;
    }

    private function ultimaSecuenciaMatricula(
        int $establecimientoId,
        int $periodoId,
        ?EstablecimientoPeriodo $periodo = null,
    ): int {
        $ultimaPeriodo = $periodo !== null
            ? (int) $periodo->ultima_secuencia_matricula
            : (int) EstablecimientoPeriodo::query()->whereKey($periodoId)->value('ultima_secuencia_matricula');

        $ultimaMatricula = (int) EstablecimientoMatricula::query()
            ->where('establecimiento_id', $establecimientoId)
            ->where('establecimiento_periodo_id', $periodoId)
            ->max('secuencia');

        return max($ultimaPeriodo, $ultimaMatricula);
    }

    public function desmatricular(EstablecimientoMatricula $matricula): void
    {
        DB::transaction(function () use ($matricula): void {
            $tieneNotas = EstablecimientoCalificacion::query()
                ->where('alumno_id', $matricula->alumno_id)
                ->where('establecimiento_periodo_id', $matricula->establecimiento_periodo_id)
                ->exists();

            if ($tieneNotas) {
                throw ValidationException::withMessages([
                    'alumno_id' => 'No puedes quitar a un alumno que ya tiene notas en este periodo.',
                ]);
            }

            EstablecimientoGrupoAlumno::query()
                ->where('alumno_id', $matricula->alumno_id)
                ->where('establecimiento_periodo_id', $matricula->establecimiento_periodo_id)
                ->delete();

            EstablecimientoAlumnoAsignatura::query()
                ->where('alumno_id', $matricula->alumno_id)
                ->where('establecimiento_periodo_id', $matricula->establecimiento_periodo_id)
                ->delete();

            $programaIds = EstablecimientoPrograma::query()
                ->where('establecimiento_periodo_id', $matricula->establecimiento_periodo_id)
                ->pluck('id');

            EstablecimientoProgramaAlumno::query()
                ->where('alumno_id', $matricula->alumno_id)
                ->whereIn('establecimiento_programa_id', $programaIds)
                ->delete();

            $matricula->delete();
        });
    }

    public function crearPrograma(EstablecimientoPeriodo $periodo, TipoPrograma $tipo, string $nombre): EstablecimientoPrograma
    {
        $nombre = trim($nombre);

        if ($nombre === '') {
            throw ValidationException::withMessages([
                'nombre' => 'Escribe el nombre del programa.',
            ]);
        }

        $this->exigirNombreProgramaLibre($periodo, $nombre);

        return EstablecimientoPrograma::query()->create([
            'establecimiento_id' => $periodo->establecimiento_id,
            'establecimiento_modalidad_jornada_id' => $periodo->establecimiento_modalidad_jornada_id,
            'establecimiento_periodo_id' => $periodo->id,
            'tipo' => $tipo,
            'nombre' => $nombre,
        ]);
    }

    public function actualizarPrograma(EstablecimientoPrograma $programa, TipoPrograma $tipo, string $nombre): EstablecimientoPrograma
    {
        $programa->loadMissing('periodo');
        $nombre = trim($nombre);

        if ($nombre === '') {
            throw ValidationException::withMessages([
                'nombre' => 'Escribe el nombre del programa.',
            ]);
        }

        $this->exigirNombreProgramaLibre($programa->periodo, $nombre, (int) $programa->id);

        $programa->update([
            'tipo' => $tipo,
            'nombre' => $nombre,
        ]);

        return $programa->refresh();
    }

    public function eliminarPrograma(EstablecimientoPrograma $programa): void
    {
        $this->sincronizarAlumnosPrograma($programa, []);
        $this->sincronizarAsignaturasPrograma($programa, []);
        $programa->delete();
    }

    private function exigirNombreProgramaLibre(EstablecimientoPeriodo $periodo, string $nombre, ?int $exceptoId = null): void
    {
        $repetido = EstablecimientoPrograma::query()
            ->where('establecimiento_modalidad_jornada_id', $periodo->establecimiento_modalidad_jornada_id)
            ->where('establecimiento_periodo_id', $periodo->id)
            ->where('nombre', $nombre)
            ->when($exceptoId !== null, fn ($query) => $query->whereKeyNot($exceptoId))
            ->exists();

        if ($repetido) {
            throw ValidationException::withMessages([
                'nombre' => 'Ya existe un programa con ese nombre en este periodo.',
            ]);
        }
    }

    /**
     * @param  list<int>  $alumnoIds
     */
    public function sincronizarAlumnosPrograma(EstablecimientoPrograma $programa, array $alumnoIds): void
    {
        $alumnoIds = array_values(array_unique($alumnoIds));
        $alumnos = $this->alumnosDe($alumnoIds);

        DB::transaction(function () use ($programa, $alumnoIds, $alumnos): void {
            foreach ($alumnos as $alumno) {
                $matricula = $this->matriculaEnPeriodo($alumno, $programa->periodo);
                $this->exigirOferta($matricula, (int) $programa->establecimiento_modalidad_jornada_id);
            }

            $quitar = $programa->membresias()
                ->whereNotIn('alumno_id', $alumnoIds === [] ? [0] : $alumnoIds)
                ->pluck('alumno_id');

            foreach ($quitar as $alumnoId) {
                $this->exigirSinNotasDePrograma($programa, (int) $alumnoId);
                $this->quitarInscripcionesDePrograma($programa, (int) $alumnoId);
            }

            $programa->membresias()->whereIn('alumno_id', $quitar)->delete();

            $actuales = $programa->membresias()->pluck('alumno_id')->map(fn (mixed $id): int => (int) $id)->all();

            foreach ($alumnoIds as $alumnoId) {
                if (in_array($alumnoId, $actuales, true)) {
                    continue;
                }

                EstablecimientoProgramaAlumno::query()->create([
                    'establecimiento_programa_id' => $programa->id,
                    'alumno_id' => $alumnoId,
                ]);
            }
        });
    }

    /**
     * @param  list<int>  $asignaturaIds
     */
    public function sincronizarAsignaturasPrograma(EstablecimientoPrograma $programa, array $asignaturaIds): void
    {
        $asignaturaIds = array_values(array_unique($asignaturaIds));

        DB::transaction(function () use ($programa, $asignaturaIds): void {
            $asignaturas = EstablecimientoAsignatura::query()
                ->whereIn('id', $asignaturaIds === [] ? [0] : $asignaturaIds)
                ->get();

            if ($asignaturas->count() !== count($asignaturaIds)) {
                throw ValidationException::withMessages([
                    'asignaturas' => 'Selecciona asignaturas de esta jornada.',
                ]);
            }

            foreach ($asignaturas as $asignatura) {
                $this->exigirAsignaturaDeOferta($asignatura, $programa->periodo);

                if ($asignatura->asignatura_propia_id === null) {
                    throw ValidationException::withMessages([
                        'asignaturas' => 'El Bachillerato Internacional usa asignaturas propias, no las del currículo nacional.',
                    ]);
                }

                if ($asignatura->establecimiento_programa_id !== null
                    && (int) $asignatura->establecimiento_programa_id !== (int) $programa->id) {
                    throw ValidationException::withMessages([
                        'asignaturas' => 'La asignatura ya pertenece a otro programa.',
                    ]);
                }

                $asignatura->update([
                    'establecimiento_programa_id' => $programa->id,
                    'tipo_asignatura' => TipoAsignatura::ProgramaInternacional,
                    'aparece_en_libreta' => false,
                    'escala' => EscalaCalificacion::BachilleratoInternacional,
                    'es_flotante' => true,
                ]);
                $asignatura->asignaturaPropia?->update([
                    'tipo_asignatura' => TipoAsignatura::ProgramaInternacional,
                ]);
            }

            $quitar = $programa->asignaturas()->whereNotIn('id', $asignaturaIds === [] ? [0] : $asignaturaIds)->get();

            foreach ($quitar as $asignatura) {
                $this->exigirSinNotasDeAsignatura($programa->periodo, $asignatura);
                EstablecimientoAlumnoAsignatura::query()
                    ->where('establecimiento_periodo_id', $programa->establecimiento_periodo_id)
                    ->where('establecimiento_asignatura_id', $asignatura->id)
                    ->delete();
                $asignatura->grupos()
                    ->where('establecimiento_periodo_id', $programa->establecimiento_periodo_id)
                    ->delete();
                $asignatura->update([
                    'establecimiento_programa_id' => null,
                    'tipo_asignatura' => TipoAsignatura::Extra,
                    'aparece_en_libreta' => false,
                    'escala' => EscalaCalificacion::Nacional,
                    'es_flotante' => false,
                ]);
            }
        });
    }

    /**
     * @param  list<int>  $alumnoIds
     */
    public function sincronizarInscripciones(EstablecimientoPeriodo $periodo, EstablecimientoAsignatura $asignatura, array $alumnoIds): void
    {
        if (! $asignatura->tipo_asignatura->exigeInscripcionIndividual()) {
            throw ValidationException::withMessages([
                'asignatura_id' => 'Esta asignatura la cursan todos los alumnos del grado.',
            ]);
        }

        $alumnoIds = array_values(array_unique($alumnoIds));
        $this->exigirAsignaturaDeOferta($asignatura, $periodo);

        DB::transaction(function () use ($periodo, $asignatura, $alumnoIds): void {
            foreach ($this->alumnosDe($alumnoIds) as $alumno) {
                $this->validarCursaAsignatura($periodo, $asignatura, $alumno, false);
            }

            $quitar = EstablecimientoAlumnoAsignatura::query()
                ->where('establecimiento_periodo_id', $periodo->id)
                ->where('establecimiento_asignatura_id', $asignatura->id)
                ->when($alumnoIds !== [], fn ($query) => $query->whereNotIn('alumno_id', $alumnoIds))
                ->pluck('alumno_id');

            foreach ($quitar as $alumnoId) {
                $tieneNotas = EstablecimientoCalificacion::query()
                    ->where('establecimiento_periodo_id', $periodo->id)
                    ->where('establecimiento_asignatura_id', $asignatura->id)
                    ->where('alumno_id', $alumnoId)
                    ->exists();

                if ($tieneNotas) {
                    throw ValidationException::withMessages([
                        'alumnos' => 'No puedes quitar la inscripción de un alumno que ya tiene notas.',
                    ]);
                }

                EstablecimientoGrupoAlumno::query()
                    ->where('establecimiento_periodo_id', $periodo->id)
                    ->where('establecimiento_asignatura_id', $asignatura->id)
                    ->where('alumno_id', $alumnoId)
                    ->delete();
            }

            EstablecimientoAlumnoAsignatura::query()
                ->where('establecimiento_periodo_id', $periodo->id)
                ->where('establecimiento_asignatura_id', $asignatura->id)
                ->when($alumnoIds !== [], fn ($query) => $query->whereNotIn('alumno_id', $alumnoIds))
                ->delete();

            $actuales = EstablecimientoAlumnoAsignatura::query()
                ->where('establecimiento_periodo_id', $periodo->id)
                ->where('establecimiento_asignatura_id', $asignatura->id)
                ->pluck('alumno_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            foreach ($alumnoIds as $alumnoId) {
                if (in_array($alumnoId, $actuales, true)) {
                    continue;
                }

                EstablecimientoAlumnoAsignatura::query()->create([
                    'establecimiento_periodo_id' => $periodo->id,
                    'establecimiento_asignatura_id' => $asignatura->id,
                    'alumno_id' => $alumnoId,
                ]);
            }
        });
    }

    public function crearGrupo(
        EstablecimientoPeriodo $periodo,
        EstablecimientoAsignatura $asignatura,
        string $nombre,
        ?Empleado $docente = null,
    ): EstablecimientoGrupo {
        $this->exigirAsignaturaDeOferta($asignatura, $periodo);
        $nombre = trim($nombre);

        if ($nombre === '') {
            throw ValidationException::withMessages([
                'nombre' => 'Escribe el nombre del grupo.',
            ]);
        }

        if ($docente !== null) {
            $docente->loadMissing('persona');

            if ((int) $docente->persona?->establecimiento_id !== (int) $periodo->establecimiento_id) {
                throw ValidationException::withMessages([
                    'empleado_id' => 'El docente no pertenece a este establecimiento.',
                ]);
            }
        }

        $repetido = EstablecimientoGrupo::query()
            ->where('establecimiento_periodo_id', $periodo->id)
            ->where('establecimiento_asignatura_id', $asignatura->id)
            ->where('nombre', $nombre)
            ->exists();

        if ($repetido) {
            throw ValidationException::withMessages([
                'nombre' => 'Ya existe un grupo con ese nombre en esta asignatura.',
            ]);
        }

        $asignatura->update(['es_flotante' => true]);

        return EstablecimientoGrupo::query()->create([
            'establecimiento_id' => $periodo->establecimiento_id,
            'establecimiento_periodo_id' => $periodo->id,
            'establecimiento_asignatura_id' => $asignatura->id,
            'empleado_id' => $docente?->id,
            'nombre' => $nombre,
        ]);
    }

    /**
     * @param  list<int>  $alumnoIds
     */
    public function sincronizarAlumnosGrupo(EstablecimientoGrupo $grupo, array $alumnoIds): void
    {
        $alumnoIds = array_values(array_unique($alumnoIds));
        $grupo->loadMissing(['asignatura', 'periodo']);

        DB::transaction(function () use ($grupo, $alumnoIds): void {
            foreach ($this->alumnosDe($alumnoIds) as $alumno) {
                $this->validarAlumnoEnGrupo($grupo, $alumno);
            }

            $grupo->miembros()
                ->when($alumnoIds !== [], fn ($query) => $query->whereNotIn('alumno_id', $alumnoIds))
                ->delete();

            $actuales = $grupo->miembros()->pluck('alumno_id')->map(fn (mixed $id): int => (int) $id)->all();

            foreach ($alumnoIds as $alumnoId) {
                if (in_array($alumnoId, $actuales, true)) {
                    continue;
                }

                EstablecimientoGrupoAlumno::query()->create([
                    'establecimiento_grupo_id' => $grupo->id,
                    'establecimiento_periodo_id' => $grupo->establecimiento_periodo_id,
                    'establecimiento_asignatura_id' => $grupo->establecimiento_asignatura_id,
                    'alumno_id' => $alumnoId,
                ]);
            }
        });
    }

    public function registrarCalificacion(
        Alumno $alumno,
        EstablecimientoPeriodo $periodo,
        EstablecimientoAsignatura $asignatura,
        int $cicloOrden,
        int $parcialNumero,
        float|string $nota,
        ?EstablecimientoGrupo $grupo = null,
    ): EstablecimientoCalificacion {
        $this->exigirAsignaturaDeOferta($asignatura, $periodo);
        $this->validarCursaAsignatura($periodo, $asignatura, $alumno);
        $this->validarCiclo($periodo, $cicloOrden, $parcialNumero);

        if ($grupo !== null) {
            $grupo->loadMissing('asignatura');

            if ((int) $grupo->establecimiento_asignatura_id !== (int) $asignatura->id
                || (int) $grupo->establecimiento_periodo_id !== (int) $periodo->id) {
                throw ValidationException::withMessages([
                    'establecimiento_grupo_id' => 'El grupo no dicta esta asignatura en el periodo.',
                ]);
            }

            $esMiembro = $grupo->miembros()->where('alumno_id', $alumno->id)->exists();

            if (! $esMiembro) {
                throw ValidationException::withMessages([
                    'alumno_id' => 'El alumno no pertenece a este grupo.',
                ]);
            }
        }

        $notaNormalizada = $this->notaNormalizada($asignatura, $nota);

        return EstablecimientoCalificacion::query()->updateOrCreate(
            [
                'establecimiento_periodo_id' => $periodo->id,
                'establecimiento_asignatura_id' => $asignatura->id,
                'alumno_id' => $alumno->id,
                'ciclo_orden' => $cicloOrden,
                'parcial_numero' => $parcialNumero,
            ],
            [
                'nota' => $notaNormalizada,
                'establecimiento_grupo_id' => $grupo?->id,
            ],
        );
    }

    private function validarAlumnoEnGrupo(EstablecimientoGrupo $grupo, Alumno $alumno): void
    {
        $grupo->loadMissing(['asignatura', 'periodo']);
        $matricula = $this->validarCursaAsignatura($grupo->periodo, $grupo->asignatura, $alumno);

        if (! $grupo->asignatura->es_flotante && $grupo->asignatura->tipo_asignatura !== TipoAsignatura::ProgramaInternacional) {
            throw ValidationException::withMessages([
                'alumno_id' => 'Esta asignatura no se dicta en un grupo flotante.',
            ]);
        }

        $enOtroGrupo = EstablecimientoGrupoAlumno::query()
            ->where('establecimiento_periodo_id', $grupo->establecimiento_periodo_id)
            ->where('establecimiento_asignatura_id', $grupo->establecimiento_asignatura_id)
            ->where('alumno_id', $alumno->id)
            ->where('establecimiento_grupo_id', '!=', $grupo->id)
            ->exists();

        if ($enOtroGrupo) {
            throw ValidationException::withMessages([
                'alumno_id' => 'El alumno ya pertenece a un grupo de esta asignatura.',
            ]);
        }

        if ($this->gradoId($matricula) !== (int) $grupo->asignatura->grado_id) {
            throw ValidationException::withMessages([
                'alumno_id' => 'El alumno está matriculado en otro grado.',
            ]);
        }
    }

    private function validarCursaAsignatura(
        EstablecimientoPeriodo $periodo,
        EstablecimientoAsignatura $asignatura,
        Alumno $alumno,
        bool $exigirInscripcion = true,
    ): EstablecimientoMatricula {
        $this->exigirMismoEstablecimientoDePeriodo($periodo, $alumno);
        $matricula = $this->matriculaEnPeriodo($alumno, $periodo);
        $this->exigirOferta($matricula, (int) $asignatura->establecimiento_modalidad_jornada_id);

        if ($this->gradoId($matricula) !== (int) $asignatura->grado_id) {
            throw ValidationException::withMessages([
                'alumno_id' => 'La asignatura no corresponde al grado en el que está matriculado el alumno.',
            ]);
        }

        if ($asignatura->tipo_asignatura === TipoAsignatura::ProgramaInternacional) {
            $pertenece = EstablecimientoProgramaAlumno::query()
                ->where('establecimiento_programa_id', $asignatura->establecimiento_programa_id)
                ->where('alumno_id', $alumno->id)
                ->exists();

            if (! $pertenece) {
                throw ValidationException::withMessages([
                    'alumno_id' => 'El alumno no pertenece al Bachillerato Internacional.',
                ]);
            }
        }

        if ($exigirInscripcion && $asignatura->tipo_asignatura->exigeInscripcionIndividual()) {
            $inscrito = EstablecimientoAlumnoAsignatura::query()
                ->where('establecimiento_periodo_id', $periodo->id)
                ->where('establecimiento_asignatura_id', $asignatura->id)
                ->where('alumno_id', $alumno->id)
                ->exists();

            if (! $inscrito) {
                throw ValidationException::withMessages([
                    'alumno_id' => 'El alumno no está inscrito en esta asignatura.',
                ]);
            }
        }

        return $matricula;
    }

    private function validarCiclo(EstablecimientoPeriodo $periodo, int $cicloOrden, int $parcialNumero): void
    {
        $existe = EstablecimientoPeriodoCiclo::query()
            ->where('establecimiento_periodo_id', $periodo->id)
            ->where('orden', $cicloOrden)
            ->exists();

        if (! $existe) {
            throw ValidationException::withMessages([
                'ciclo_orden' => 'El quimestre no pertenece a este periodo.',
            ]);
        }

        if ($parcialNumero === 0) {
            return;
        }

        if ($parcialNumero < 1 || $periodo->numero_parciales === null || $parcialNumero > $periodo->numero_parciales) {
            throw ValidationException::withMessages([
                'parcial_numero' => 'El parcial no está configurado en este periodo.',
            ]);
        }
    }

    private function notaNormalizada(EstablecimientoAsignatura $asignatura, float|string $nota): string
    {
        if (! is_numeric($nota)) {
            throw ValidationException::withMessages([
                'nota' => 'La nota debe ser un número.',
            ]);
        }

        if ($asignatura->tipo_calificacion !== TipoCalificacion::Cuantitativa) {
            throw ValidationException::withMessages([
                'nota' => 'Esta asignatura no usa una nota numérica.',
            ]);
        }

        $valor = round((float) $nota, 2);

        if (! $asignatura->escala->contiene($valor)) {
            throw ValidationException::withMessages([
                'nota' => $asignatura->escala->mensajeRango(),
            ]);
        }

        return number_format($valor, 2, '.', '');
    }

    /**
     * @param  list<int>  $alumnoIds
     * @return Collection<int, Alumno>
     */
    private function alumnosDe(array $alumnoIds): Collection
    {
        if ($alumnoIds === []) {
            return collect();
        }

        $alumnos = Alumno::query()->with('persona')->whereIn('id', $alumnoIds)->get();

        if ($alumnos->count() !== count($alumnoIds)) {
            throw ValidationException::withMessages([
                'alumnos' => 'Selecciona alumnos del establecimiento.',
            ]);
        }

        return $alumnos;
    }

    private function matriculaEnPeriodo(Alumno $alumno, EstablecimientoPeriodo $periodo): EstablecimientoMatricula
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

    private function exigirOferta(EstablecimientoMatricula $matricula, int $ofertaId): void
    {
        $matricula->loadMissing('aula');

        if ((int) $matricula->aula->establecimiento_modalidad_jornada_id !== $ofertaId) {
            throw ValidationException::withMessages([
                'alumno_id' => 'El alumno está matriculado en otra jornada.',
            ]);
        }
    }

    private function exigirAsignaturaDeOferta(EstablecimientoAsignatura $asignatura, EstablecimientoPeriodo $periodo): void
    {
        if ((int) $asignatura->establecimiento_id !== (int) $periodo->establecimiento_id
            || (int) $asignatura->establecimiento_modalidad_jornada_id !== (int) $periodo->establecimiento_modalidad_jornada_id) {
            throw ValidationException::withMessages([
                'establecimiento_asignatura_id' => 'La asignatura no pertenece a la jornada de este periodo.',
            ]);
        }
    }

    private function exigirMismoEstablecimiento(EstablecimientoAula $aula, Alumno $alumno): void
    {
        $alumno->loadMissing('persona');

        if ((int) $alumno->persona?->establecimiento_id !== (int) $aula->establecimiento_id) {
            throw ValidationException::withMessages([
                'alumno_id' => 'El alumno no pertenece a este establecimiento.',
            ]);
        }
    }

    private function exigirMismoEstablecimientoDePeriodo(EstablecimientoPeriodo $periodo, Alumno $alumno): void
    {
        $alumno->loadMissing('persona');

        if ((int) $alumno->persona?->establecimiento_id !== (int) $periodo->establecimiento_id) {
            throw ValidationException::withMessages([
                'alumno_id' => 'El alumno no pertenece a este establecimiento.',
            ]);
        }
    }

    private function exigirSinNotasDePrograma(EstablecimientoPrograma $programa, int $alumnoId): void
    {
        $asignaturaIds = $programa->asignaturas()->pluck('id');

        $tieneNotas = EstablecimientoCalificacion::query()
            ->where('alumno_id', $alumnoId)
            ->where('establecimiento_periodo_id', $programa->establecimiento_periodo_id)
            ->whereIn('establecimiento_asignatura_id', $asignaturaIds)
            ->exists();

        if ($tieneNotas) {
            throw ValidationException::withMessages([
                'alumnos' => 'No puedes quitar del programa a un alumno que ya tiene notas.',
            ]);
        }
    }

    private function quitarInscripcionesDePrograma(EstablecimientoPrograma $programa, int $alumnoId): void
    {
        $asignaturaIds = $programa->asignaturas()->pluck('id');

        EstablecimientoAlumnoAsignatura::query()
            ->where('alumno_id', $alumnoId)
            ->where('establecimiento_periodo_id', $programa->establecimiento_periodo_id)
            ->whereIn('establecimiento_asignatura_id', $asignaturaIds)
            ->delete();

        EstablecimientoGrupoAlumno::query()
            ->where('alumno_id', $alumnoId)
            ->where('establecimiento_periodo_id', $programa->establecimiento_periodo_id)
            ->whereIn('establecimiento_asignatura_id', $asignaturaIds)
            ->delete();
    }

    private function exigirSinNotasDeAsignatura(EstablecimientoPeriodo $periodo, EstablecimientoAsignatura $asignatura): void
    {
        $tieneNotas = EstablecimientoCalificacion::query()
            ->where('establecimiento_periodo_id', $periodo->id)
            ->where('establecimiento_asignatura_id', $asignatura->id)
            ->exists();

        if ($tieneNotas) {
            throw ValidationException::withMessages([
                'asignaturas' => 'No puedes quitar una asignatura del programa que ya tiene notas.',
            ]);
        }
    }
}
