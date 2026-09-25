<?php

namespace App\Services;

use App\Models\Establecimiento;
use App\Models\EstablecimientoModalidadJornada;
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
        EstablecimientoModalidadJornada $oferta,
        array $gradoIds,
        array $nombresGrados = [],
        array $nombresSubniveles = [],
    ): void {
        $grados = Sys_Grado::query()
            ->with('subnivel')
            ->whereIn('id', $gradoIds)
            ->get();

        $nivelSync = [];
        $subnivelSync = [];
        $gradoSync = [];

        foreach ($grados as $grado) {
            $nivelId = $grado->subnivel?->nivel_id;

            if ($grado->subnivel_id === null || $nivelId === null) {
                continue;
            }

            $pivotEstablecimiento = ['establecimiento_id' => $establecimiento->id];

            $nivelSync[(int) $nivelId] = $pivotEstablecimiento;
            $subnivelSync[(int) $grado->subnivel_id] = [
                ...$pivotEstablecimiento,
                'nivel_id' => (int) $nivelId,
                'nombre' => $nombresSubniveles[(int) $grado->subnivel_id] ?? null,
            ];
            $gradoSync[(int) $grado->id] = [
                ...$pivotEstablecimiento,
                'subnivel_id' => (int) $grado->subnivel_id,
                'nombre' => $nombresGrados[(int) $grado->id] ?? null,
            ];
        }

        DB::transaction(function () use ($oferta, $nivelSync, $subnivelSync, $gradoSync): void {
            $oferta->niveles()->sync($nivelSync);
            $oferta->subniveles()->sync($subnivelSync);
            $oferta->grados()->sync($gradoSync);
        });
    }
}
