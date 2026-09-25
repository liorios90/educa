<?php

namespace App\Services;

use App\Models\Establecimiento;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use Illuminate\Support\Facades\DB;

class SyncEstablecimientoModalidades
{
    /**
     * @param  array<int, list<int>>  $jornadasPorModalidad
     */
    public function handle(Establecimiento $establecimiento, array $jornadasPorModalidad): void
    {
        DB::transaction(function () use ($establecimiento, $jornadasPorModalidad): void {
            $keepModalidadIds = array_map(intval(...), array_keys($jornadasPorModalidad));

            $establecimiento->establecimientoModalidades()
                ->whereNotIn('modalidad_id', $keepModalidadIds)
                ->get()
                ->each(fn (EstablecimientoModalidad $modalidad) => $modalidad->delete());

            foreach ($jornadasPorModalidad as $modalidadId => $jornadaIds) {
                $establecimientoModalidad = $establecimiento->establecimientoModalidades()->firstOrCreate([
                    'modalidad_id' => (int) $modalidadId,
                ]);

                $keepJornadaIds = array_values(array_unique(array_map(intval(...), $jornadaIds)));

                $establecimientoModalidad->establecimientoJornadas()
                    ->whereNotIn('jornada_id', $keepJornadaIds)
                    ->get()
                    ->each(fn (EstablecimientoModalidadJornada $oferta) => $oferta->delete());

                foreach ($keepJornadaIds as $jornadaId) {
                    $establecimientoModalidad->establecimientoJornadas()->firstOrCreate([
                        'jornada_id' => $jornadaId,
                    ]);
                }
            }

            $this->asignarEstructuraPendiente($establecimiento);
        });
    }

    private function asignarEstructuraPendiente(Establecimiento $establecimiento): void
    {
        $ofertaIds = $establecimiento->establecimientoModalidades()
            ->with('establecimientoJornadas')
            ->get()
            ->flatMap(fn (EstablecimientoModalidad $modalidad) => $modalidad->establecimientoJornadas->pluck('id'))
            ->values();

        if ($ofertaIds->isEmpty()) {
            return;
        }

        foreach (['establecimiento_niveles', 'establecimiento_subniveles', 'establecimiento_grados'] as $table) {
            $pendientes = DB::table($table)
                ->where('establecimiento_id', $establecimiento->id)
                ->whereNull('establecimiento_modalidad_jornada_id')
                ->get();

            if ($pendientes->isEmpty()) {
                continue;
            }

            foreach ($ofertaIds as $ofertaId) {
                foreach ($pendientes as $row) {
                    $copy = (array) $row;
                    unset($copy['id']);
                    $copy['establecimiento_modalidad_jornada_id'] = $ofertaId;
                    DB::table($table)->insert($copy);
                }
            }

            DB::table($table)
                ->where('establecimiento_id', $establecimiento->id)
                ->whereNull('establecimiento_modalidad_jornada_id')
                ->delete();
        }
    }
}
