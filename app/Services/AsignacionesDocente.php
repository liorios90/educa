<?php

namespace App\Services;

use App\Auth\DocenteContexto;
use App\Enums\EsquemaCiclo;
use App\Enums\TipoCalificacion;
use App\Enums\TipoCierreEvaluacion;
use App\Models\Alumno;
use App\Models\EstablecimientoAlumnoAsignatura;
use App\Models\EstablecimientoAulaAsignaturaCiclo;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoCierreActividad;
use App\Models\EstablecimientoGrupoAlumno;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\EstablecimientoMatricula;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\EstablecimientoPeriodoConfiguracionGrado;
use App\Models\EstablecimientoPeriodoConfiguracionGradoCiclo;
use App\Models\TipoInsumo;
use App\Models\TipoInsumoDetalle;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AsignacionesDocente
{
    /**
     * @return Collection<int, EstablecimientoAulaDocente>
     */
    public function forUser(User $user): Collection
    {
        $user->loadMissing('persona.empleados');

        $empleadoIds = $user->persona?->empleados->pluck('id')->all() ?? [];

        if ($empleadoIds === [] || $user->establecimiento_id === null) {
            return collect();
        }

        return EstablecimientoAulaDocente::query()
            ->with([
                'malla.asignatura',
                'malla.asignaturaPropia',
                'aula.establecimientoGrado.grado',
                'aula.periodo.ciclos',
            ])
            ->whereIn('empleado_id', $empleadoIds)
            ->whereHas('aula', fn ($query) => $query
                ->where('establecimiento_id', $user->establecimiento_id)
                ->whereHas('periodo', fn ($periodo) => $periodo->activo()))
            ->get()
            ->filter(fn (EstablecimientoAulaDocente $asignacion): bool => $asignacion->malla?->asignatura !== null || $asignacion->malla?->asignaturaPropia !== null
            )
            ->values();
    }

    /**
     * @return list<array{
     *     id: int,
     *     etiqueta: string,
     *     ciclo_label: string,
     *     materias: list<array{id: int, nombre: string, insumos: list<array{id: int, etiqueta: string}>}>,
     *     ciclos: list<array{id: int, nombre: string}>,
     *     parciales: list<array{numero: int, nombre: string}>
     * }>
     */
    public function aulasSelector(User $user): array
    {
        $asignaciones = $this->forUser($user);
        $insumosPorMateria = $this->insumosPorMateria($asignaciones);

        return $asignaciones
            ->groupBy('establecimiento_aula_id')
            ->map(function (Collection $grupo) use ($insumosPorMateria): ?array {
                /** @var EstablecimientoAulaDocente $primera */
                $primera = $grupo->first();
                $aula = $primera->aula;

                if ($aula === null) {
                    return null;
                }

                $periodo = $aula->periodo;
                $cicloLabel = match ($periodo?->esquema_ciclo) {
                    EsquemaCiclo::Quimestres => 'Quimestres',
                    EsquemaCiclo::Trimestres => 'Trimestres',
                    default => 'Ciclos',
                };
                $numeroParciales = (int) ($periodo?->numero_parciales ?? 0);

                $materias = $grupo
                    ->map(fn (EstablecimientoAulaDocente $asignacion): array => [
                        'id' => (int) $asignacion->establecimiento_asignatura_id,
                        'nombre' => $asignacion->malla->nombre(),
                        'insumos' => $insumosPorMateria[(int) $aula->id.':'.(int) $asignacion->establecimiento_asignatura_id] ?? [],
                    ])
                    ->unique('id')
                    ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all();

                $ciclos = ($periodo?->ciclos ?? collect())
                    ->map(fn (EstablecimientoPeriodoCiclo $ciclo): array => [
                        'id' => (int) $ciclo->id,
                        'nombre' => $ciclo->nombre,
                    ])
                    ->values()
                    ->all();

                $parciales = $numeroParciales < 1
                    ? []
                    : collect(range(1, $numeroParciales))
                        ->map(fn (int $numero): array => [
                            'numero' => $numero,
                            'nombre' => $this->etiquetaParcial($numero),
                        ])
                        ->all();

                return [
                    'id' => (int) $aula->id,
                    'etiqueta' => $aula->etiqueta(),
                    'ciclo_label' => $cicloLabel,
                    'materias' => $materias,
                    'ciclos' => $ciclos,
                    'parciales' => $parciales,
                ];
            })
            ->filter()
            ->sortBy('etiqueta', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    public function contexto(User $user, int $aulaId, int $asignaturaId, int $cicloId, int $parcialNumero): ?DocenteContexto
    {
        $asignacion = $this->forUser($user)->first(
            fn (EstablecimientoAulaDocente $asignacion): bool => (int) $asignacion->establecimiento_aula_id === $aulaId
                && (int) $asignacion->establecimiento_asignatura_id === $asignaturaId,
        );

        if ($asignacion === null || $asignacion->aula === null || $asignacion->malla === null) {
            return null;
        }

        $ciclo = $asignacion->aula->periodo?->ciclos->first(
            fn (EstablecimientoPeriodoCiclo $ciclo): bool => (int) $ciclo->id === $cicloId,
        );

        if ($ciclo === null) {
            return null;
        }

        $numeroParciales = (int) ($asignacion->aula->periodo?->numero_parciales ?? 0);

        if ($parcialNumero !== 0 && ($parcialNumero < 1 || $parcialNumero > $numeroParciales)) {
            return null;
        }

        return new DocenteContexto($asignacion->aula, $asignacion->malla, $ciclo, $parcialNumero);
    }

    public function contextoCierre(
        User $user,
        int $aulaId,
        int $asignaturaId,
        TipoCierreEvaluacion $tipo,
        ?int $cicloId,
    ): ?DocenteContexto {
        if ($tipo->esFinal()) {
            $asignacion = $this->forUser($user)->first(
                fn (EstablecimientoAulaDocente $asignacion): bool => (int) $asignacion->establecimiento_aula_id === $aulaId
                    && (int) $asignacion->establecimiento_asignatura_id === $asignaturaId,
            );

            $cicloId = $asignacion?->aula?->periodo?->ciclos->first()?->id;
        }

        if (! is_numeric($cicloId) || (int) $cicloId < 1) {
            return null;
        }

        return $this->contexto($user, $aulaId, $asignaturaId, (int) $cicloId, 0);
    }

    public function cierreDelContexto(DocenteContexto $contexto, TipoCierreEvaluacion $tipo): ?EstablecimientoCierreActividad
    {
        return EstablecimientoCierreActividad::query()
            ->with('calificaciones')
            ->where('establecimiento_aula_id', $contexto->aula->id)
            ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
            ->where('tipo', $tipo)
            ->when(
                ! $tipo->esFinal(),
                fn ($query) => $query->where('establecimiento_periodo_ciclo_id', $contexto->ciclo->id),
            )
            ->first();
    }

    public function cicloTieneCierre(DocenteContexto $contexto, TipoCierreEvaluacion $tipo): bool
    {
        return $this->pesoCierre($contexto, $tipo) > 0.009;
    }

    public function pesoCierre(DocenteContexto $contexto, TipoCierreEvaluacion $tipo): float
    {
        if ($tipo->esFinal()) {
            return $this->pesoCierreFinal($contexto, $tipo);
        }

        $campo = $tipo === TipoCierreEvaluacion::Examen ? 'porcentaje_examen' : 'porcentaje_proyecto';

        $porAsignatura = EstablecimientoAulaAsignaturaCiclo::query()
            ->where('establecimiento_periodo_id', $contexto->aula->establecimiento_periodo_id)
            ->where('establecimiento_aula_id', $contexto->aula->id)
            ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
            ->where('ciclo_orden', $contexto->ciclo->orden)
            ->first();

        if ($porAsignatura !== null) {
            return (float) $porAsignatura->{$campo};
        }

        $porGrado = $this->cierrePorGrado($contexto);

        if ($porGrado !== null) {
            return (float) $porGrado->{$campo};
        }

        return (float) $contexto->ciclo->{$campo};
    }

    private function pesoCierreFinal(DocenteContexto $contexto, TipoCierreEvaluacion $tipo): float
    {
        $campo = $tipo === TipoCierreEvaluacion::ExamenFinal
            ? 'porcentaje_examen_final'
            : 'porcentaje_proyecto_final';

        $porGrado = $this->configuracionGrado($contexto);

        if ($porGrado !== null) {
            return (float) $porGrado->{$campo};
        }

        $contexto->aula->loadMissing('periodo');

        return (float) ($contexto->aula->periodo?->{$campo} ?? 0);
    }

    private function configuracionGrado(DocenteContexto $contexto): ?EstablecimientoPeriodoConfiguracionGrado
    {
        $contexto->aula->loadMissing('establecimientoGrado');
        $gradoId = $contexto->aula->establecimientoGrado?->grado_id;

        if ($gradoId === null) {
            return null;
        }

        return EstablecimientoPeriodoConfiguracionGrado::query()
            ->where('establecimiento_periodo_id', $contexto->aula->establecimiento_periodo_id)
            ->whereHas('grados', fn ($grados) => $grados->whereKey($gradoId))
            ->first();
    }

    private function cierrePorGrado(DocenteContexto $contexto): ?EstablecimientoPeriodoConfiguracionGradoCiclo
    {
        $configuracion = $this->configuracionGrado($contexto);

        if ($configuracion === null) {
            return null;
        }

        return EstablecimientoPeriodoConfiguracionGradoCiclo::query()
            ->where('configuracion_grado_id', $configuracion->id)
            ->where('ciclo_orden', $contexto->ciclo->orden)
            ->first();
    }

    public function detalleAsignado(int $aulaId, int $asignaturaId, int $detalleId): ?TipoInsumoDetalle
    {
        $tipoId = DB::table('establecimiento_aula_asignatura_tipo_insumos')
            ->where('establecimiento_aula_id', $aulaId)
            ->where('establecimiento_asignatura_id', $asignaturaId)
            ->value('tipo_insumo_id');

        if (! is_numeric($tipoId)) {
            return null;
        }

        return TipoInsumoDetalle::query()
            ->whereKey($detalleId)
            ->where('tipo_insumo_id', $tipoId)
            ->where('activo', true)
            ->first();
    }

    /**
     * @return Collection<int, EstablecimientoInsumoActividad>
     */
    public function actividades(DocenteContexto $contexto): Collection
    {
        return EstablecimientoInsumoActividad::query()
            ->with('detalle')
            ->where('establecimiento_aula_id', $contexto->aula->id)
            ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
            ->where('establecimiento_periodo_ciclo_id', $contexto->ciclo->id)
            ->where('parcial_numero', $contexto->parcialNumero)
            ->orderBy('id')
            ->get();
    }

    public function actividadDelContexto(DocenteContexto $contexto, int $actividadId): ?EstablecimientoInsumoActividad
    {
        return EstablecimientoInsumoActividad::query()
            ->with(['detalle', 'calificaciones'])
            ->whereKey($actividadId)
            ->where('establecimiento_aula_id', $contexto->aula->id)
            ->where('establecimiento_asignatura_id', $contexto->asignatura->id)
            ->where('establecimiento_periodo_ciclo_id', $contexto->ciclo->id)
            ->where('parcial_numero', $contexto->parcialNumero)
            ->first();
    }

    /**
     * @return list<array{id: int, etiqueta: string, actividades: list<array{id: int, nombre: string}>}>
     */
    public function actividadesAgrupadas(DocenteContexto $contexto): array
    {
        return $this->actividades($contexto)
            ->groupBy('tipo_insumo_detalle_id')
            ->map(function (Collection $grupo): ?array {
                /** @var EstablecimientoInsumoActividad $primera */
                $primera = $grupo->first();
                $detalle = $primera->detalle;

                if ($detalle === null) {
                    return null;
                }

                return [
                    'id' => (int) $detalle->id,
                    'etiqueta' => $this->etiquetaInsumo($detalle),
                    'actividades' => $grupo
                        ->map(fn (EstablecimientoInsumoActividad $actividad): array => [
                            'id' => (int) $actividad->id,
                            'nombre' => $actividad->nombre,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Roster for the teacher's selected classroom.
     *
     * Optional and international-programme subjects use individual inscriptions.
     * A common subject may be marked floating because a group exists; classroom
     * enrollment still defines who appears when the teacher grades that aula.
     *
     * @return Collection<int, Alumno>
     */
    public function alumnos(DocenteContexto $contexto): Collection
    {
        $idsDelAula = $this->alumnoIdsMatriculadosEnAula((int) $contexto->aula->id);
        $asignatura = $contexto->asignatura;

        if ($asignatura->tipo_asignatura->exigeInscripcionIndividual()) {
            $inscritos = $this->alumnoIdsDeAsignatura(
                (int) $contexto->aula->establecimiento_periodo_id,
                (int) $asignatura->id,
            );
            $enAula = $idsDelAula->intersect($inscritos)->values();

            return $this->alumnosPorIds($enAula->isNotEmpty() ? $enAula : $inscritos);
        }

        if ($asignatura->es_flotante && $idsDelAula->isEmpty()) {
            return $this->alumnosPorIds($this->alumnoIdsDeAsignatura(
                (int) $contexto->aula->establecimiento_periodo_id,
                (int) $asignatura->id,
            ));
        }

        return $this->alumnosPorIds($idsDelAula);
    }

    public function tipoCalificacion(DocenteContexto $contexto): TipoCalificacion
    {
        $tipo = $this->tipoInsumoAsignado((int) $contexto->aula->id, (int) $contexto->asignatura->id);

        return TipoCalificacion::tryFrom((string) $tipo?->tipo_calificacion)
            ?? $contexto->asignatura->tipo_calificacion;
    }

    /**
     * @return list<array{valor: string, etiqueta: string}>
     */
    public function valoresCualitativos(DocenteContexto $contexto): array
    {
        $tipo = $this->tipoInsumoAsignado((int) $contexto->aula->id, (int) $contexto->asignatura->id);

        if ($tipo === null) {
            return [];
        }

        return $tipo->detalles()
            ->where('activo', true)
            ->orderBy('id')
            ->get()
            ->map(function (TipoInsumoDetalle $detalle): array {
                $valor = trim((string) ($detalle->valor !== null && $detalle->valor !== '' ? $detalle->valor : $detalle->nombre));
                $etiqueta = $detalle->nombre;

                if ($detalle->valor !== null && $detalle->valor !== '' && $detalle->valor !== $detalle->nombre) {
                    $etiqueta = $detalle->nombre.' ('.$detalle->valor.')';
                }

                return [
                    'valor' => $valor,
                    'etiqueta' => $etiqueta,
                ];
            })
            ->filter(fn (array $item): bool => $item['valor'] !== '')
            ->unique('valor')
            ->values()
            ->all();
    }

    public function tipoInsumoAsignado(int $aulaId, int $asignaturaId): ?TipoInsumo
    {
        $tipoId = DB::table('establecimiento_aula_asignatura_tipo_insumos')
            ->where('establecimiento_aula_id', $aulaId)
            ->where('establecimiento_asignatura_id', $asignaturaId)
            ->value('tipo_insumo_id');

        if (! is_numeric($tipoId)) {
            return null;
        }

        return TipoInsumo::query()
            ->whereKey($tipoId)
            ->where('activo', true)
            ->first();
    }

    /**
     * @param  Collection<int, EstablecimientoAulaDocente>  $asignaciones
     * @return array<string, list<array{id: int, etiqueta: string}>>
     */
    private function insumosPorMateria(Collection $asignaciones): array
    {
        if ($asignaciones->isEmpty()) {
            return [];
        }

        $tipos = DB::table('establecimiento_aula_asignatura_tipo_insumos as asignacion')
            ->join('tipos_insumos as tipo', 'tipo.id', '=', 'asignacion.tipo_insumo_id')
            ->whereIn('asignacion.establecimiento_aula_id', $asignaciones->pluck('establecimiento_aula_id'))
            ->whereIn('asignacion.establecimiento_asignatura_id', $asignaciones->pluck('establecimiento_asignatura_id'))
            ->where('tipo.activo', true)
            ->get([
                'asignacion.establecimiento_aula_id',
                'asignacion.establecimiento_asignatura_id',
                'asignacion.tipo_insumo_id',
            ]);

        if ($tipos->isEmpty()) {
            return [];
        }

        $detalles = TipoInsumoDetalle::query()
            ->whereIn('tipo_insumo_id', $tipos->pluck('tipo_insumo_id'))
            ->where('activo', true)
            ->orderBy('id')
            ->get()
            ->groupBy('tipo_insumo_id');

        $porMateria = [];

        foreach ($tipos as $tipo) {
            $clave = (int) $tipo->establecimiento_aula_id.':'.(int) $tipo->establecimiento_asignatura_id;
            $porMateria[$clave] = ($detalles->get($tipo->tipo_insumo_id) ?? collect())
                ->map(fn (TipoInsumoDetalle $detalle): array => [
                    'id' => (int) $detalle->id,
                    'etiqueta' => $this->etiquetaInsumo($detalle),
                ])
                ->values()
                ->all();
        }

        return $porMateria;
    }

    private function etiquetaParcial(int $numero): string
    {
        $ordinal = match ($numero) {
            1 => '1er',
            2 => '2do',
            3 => '3er',
            4 => '4to',
            5 => '5to',
            6 => '6to',
            default => $numero.'º',
        };

        return $ordinal.' Parcial';
    }

    /**
     * @return Collection<int, int>
     */
    private function alumnoIdsMatriculadosEnAula(int $aulaId): Collection
    {
        return EstablecimientoMatricula::query()
            ->where('establecimiento_aula_id', $aulaId)
            ->pluck('alumno_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    private function alumnoIdsDeAsignatura(int $periodoId, int $asignaturaId): Collection
    {
        $inscritos = EstablecimientoAlumnoAsignatura::query()
            ->where('establecimiento_periodo_id', $periodoId)
            ->where('establecimiento_asignatura_id', $asignaturaId)
            ->pluck('alumno_id');

        $enGrupos = EstablecimientoGrupoAlumno::query()
            ->where('establecimiento_periodo_id', $periodoId)
            ->where('establecimiento_asignatura_id', $asignaturaId)
            ->pluck('alumno_id');

        return $inscritos
            ->merge($enGrupos)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * @param  Collection<int, int>  $ids
     * @return Collection<int, Alumno>
     */
    private function alumnosPorIds(Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        return Alumno::query()
            ->select('alumnos.*')
            ->join('personas', 'personas.id', '=', 'alumnos.persona_id')
            ->with('persona')
            ->whereIn('alumnos.id', $ids->all())
            ->orderBy('personas.apellidos')
            ->orderBy('personas.nombres')
            ->get();
    }

    private function etiquetaInsumo(TipoInsumoDetalle $detalle): string
    {
        if ($detalle->porcentaje === null) {
            return $detalle->nombre;
        }

        $porcentaje = rtrim(rtrim(number_format((float) $detalle->porcentaje, 2, '.', ''), '0'), '.');

        return $detalle->nombre.' ('.$porcentaje.' %)';
    }
}
