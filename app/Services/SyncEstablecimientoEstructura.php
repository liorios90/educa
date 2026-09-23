<?php

namespace App\Services;

use App\Models\Establecimiento;
use App\Models\Sys_Grado;
use Illuminate\Support\Facades\DB;

class SyncEstablecimientoEstructura
{
    /**
     * @param  list<int>  $gradoIds
     * @param  array<int, string|null>  $nombresGrados
     * @param  array<int, string|null>  $nombresSubniveles
     */
    public function handle(
        Establecimiento $establecimiento,
        array $gradoIds,
        array $nombresGrados = [],
        array $nombresSubniveles = [],
    ): void {
        $grados = Sys_Grado::query()
            ->with('subnivel')
            ->whereIn('id', $gradoIds)
            ->get();

        $nivelIds = [];
        $subnivelSync = [];
        $gradoSync = [];

        foreach ($grados as $grado) {
            $nivelId = $grado->subnivel?->nivel_id;

            if ($grado->subnivel_id === null || $nivelId === null) {
                continue;
            }

            $nivelIds[] = (int) $nivelId;
            $subnivelSync[(int) $grado->subnivel_id] = [
                'nivel_id' => (int) $nivelId,
                'nombre' => $nombresSubniveles[(int) $grado->subnivel_id] ?? null,
            ];
            $gradoSync[(int) $grado->id] = [
                'subnivel_id' => (int) $grado->subnivel_id,
                'nombre' => $nombresGrados[(int) $grado->id] ?? null,
            ];
        }

        $nivelIds = array_values(array_unique($nivelIds));

        DB::transaction(function () use ($establecimiento, $nivelIds, $subnivelSync, $gradoSync): void {
            $establecimiento->niveles()->sync($nivelIds);
            $establecimiento->subniveles()->sync($subnivelSync);
            $establecimiento->grados()->sync($gradoSync);
        });
    }
}
