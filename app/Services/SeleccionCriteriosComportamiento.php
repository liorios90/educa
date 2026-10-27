<?php

namespace App\Services;

use App\Enums\TipoCriterioComportamiento;
use App\Models\EstablecimientoPeriodo;
use App\Models\Sys_CriterioComportamiento;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SeleccionCriteriosComportamiento
{
    public const MENSAJE_LINEAMIENTO = 'Además de los criterios obligatorios, elige 1 habilidad cognitiva, 3 sociales y 1 emocional.';

    /**
     * Cupos de habilidades no obligatorias que cada periodo debe añadir.
     *
     * @return array<string, int>
     */
    public static function cuposOpcionales(): array
    {
        return [
            TipoCriterioComportamiento::Cognitiva->value => 1,
            TipoCriterioComportamiento::Social->value => 3,
            TipoCriterioComportamiento::Emocional->value => 1,
        ];
    }

    /**
     * @param  list<int|string>  $ids
     */
    public function guardar(EstablecimientoPeriodo $periodo, array $ids): void
    {
        $solicitados = collect($ids)->map(fn (mixed $id): int => (int) $id)->unique()->values();
        $activos = Sys_CriterioComportamiento::query()->activo()->get();
        $activosIds = $activos->map(fn (Sys_CriterioComportamiento $criterio): int => (int) $criterio->id);

        if ($solicitados->diff($activosIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'criterios' => 'Selecciona solo criterios activos del catálogo.',
            ]);
        }

        $seleccion = $activos->filter(
            fn (Sys_CriterioComportamiento $criterio): bool => $criterio->obligatorio || $solicitados->contains((int) $criterio->id),
        )->values();

        foreach (self::cuposOpcionales() as $tipo => $cupo) {
            $elegidos = $this->opcionalesDe($seleccion, $tipo);

            if ($elegidos->count() !== $cupo) {
                throw ValidationException::withMessages([
                    'criterios' => self::MENSAJE_LINEAMIENTO,
                ]);
            }
        }

        $periodo->criteriosComportamiento()->sync(
            $seleccion->map(fn (Sys_CriterioComportamiento $criterio): int => (int) $criterio->id)->all(),
        );
    }

    /**
     * @param  Collection<int, Sys_CriterioComportamiento>  $seleccion
     * @return Collection<int, Sys_CriterioComportamiento>
     */
    private function opcionalesDe(Collection $seleccion, string $tipo): Collection
    {
        return $seleccion->filter(
            fn (Sys_CriterioComportamiento $criterio): bool => ! $criterio->obligatorio && $criterio->tipo->value === $tipo,
        )->values();
    }
}
