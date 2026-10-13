<?php

namespace App\Services;

use App\Enums\TipoCalificacion;
use App\Models\EstablecimientoAsignatura;
use Illuminate\Support\Collection;

class GradosConPonderacionCuantitativa
{
    /**
     * @return Collection<int, array{nombre: string, asignaturas: Collection<int, EstablecimientoAsignatura>}>
     */
    public function paraOferta(?int $ofertaId): Collection
    {
        if ($ofertaId === null) {
            return collect();
        }

        return EstablecimientoAsignatura::query()
            ->where('establecimiento_modalidad_jornada_id', $ofertaId)
            ->where('aparece_en_libreta', true)
            ->with(['grado', 'asignatura', 'asignaturaPropia'])
            ->get()
            ->groupBy('grado_id')
            ->map(function (Collection $asignaturas): ?array {
                if ($asignaturas->contains(fn (EstablecimientoAsignatura $asignatura): bool => $asignatura->califica_por_destrezas)) {
                    return null;
                }

                $cuantitativas = $asignaturas
                    ->filter(fn (EstablecimientoAsignatura $asignatura): bool => $asignatura->tipo_calificacion === TipoCalificacion::Cuantitativa)
                    ->values();

                if ($cuantitativas->isEmpty()) {
                    return null;
                }

                $grado = $cuantitativas->first()->grado;

                return [
                    'nombre' => $grado?->nombre ?? 'Grado',
                    'asignaturas' => $cuantitativas,
                ];
            })
            ->filter()
            ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE);
    }
}
