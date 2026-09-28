<?php

namespace App\Services;

use App\Enums\ModoLibretaArea;
use App\Enums\TipoCalificacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAreaLibreta;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoModalidadJornada;
use Illuminate\Support\Facades\DB;

class SyncEstablecimientoAsignaturas
{
    /**
     * @param  list<array{
     *     asignatura_id: int,
     *     grado_id: int,
     *     aparece_en_libreta: bool,
     *     horas_semanales: int|null,
     *     tipo_calificacion: TipoCalificacion
     * }>  $filas
     * @param  list<array{
     *     area_id: int,
     *     grado_id: int,
     *     modo_libreta: ModoLibretaArea
     * }>  $modosArea
     */
    public function handle(
        Establecimiento $establecimiento,
        EstablecimientoModalidadJornada $oferta,
        array $filas,
        array $modosArea = [],
    ): void {
        DB::transaction(function () use ($establecimiento, $oferta, $filas, $modosArea): void {
            EstablecimientoAsignatura::query()
                ->where('establecimiento_modalidad_jornada_id', $oferta->id)
                ->delete();

            EstablecimientoAreaLibreta::query()
                ->where('establecimiento_modalidad_jornada_id', $oferta->id)
                ->delete();

            foreach ($filas as $fila) {
                EstablecimientoAsignatura::query()->create([
                    'establecimiento_id' => $establecimiento->id,
                    'establecimiento_modalidad_jornada_id' => $oferta->id,
                    'asignatura_id' => $fila['asignatura_id'],
                    'grado_id' => $fila['grado_id'],
                    'aparece_en_libreta' => $fila['aparece_en_libreta'],
                    'horas_semanales' => $fila['horas_semanales'],
                    'tipo_calificacion' => $fila['tipo_calificacion'],
                ]);
            }

            foreach ($modosArea as $modo) {
                EstablecimientoAreaLibreta::query()->create([
                    'establecimiento_id' => $establecimiento->id,
                    'establecimiento_modalidad_jornada_id' => $oferta->id,
                    'area_id' => $modo['area_id'],
                    'grado_id' => $modo['grado_id'],
                    'modo_libreta' => $modo['modo_libreta'],
                ]);
            }
        });
    }

    public function pruneGradosFueraDeOferta(EstablecimientoModalidadJornada $oferta, array $gradoIds): void
    {
        $malla = EstablecimientoAsignatura::query()
            ->where('establecimiento_modalidad_jornada_id', $oferta->id);
        $areas = EstablecimientoAreaLibreta::query()
            ->where('establecimiento_modalidad_jornada_id', $oferta->id);

        if ($gradoIds === []) {
            $malla->delete();
            $areas->delete();

            return;
        }

        $malla->whereNotIn('grado_id', $gradoIds)->delete();
        $areas->whereNotIn('grado_id', $gradoIds)->delete();
    }
}
