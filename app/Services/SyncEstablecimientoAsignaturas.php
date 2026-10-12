<?php

namespace App\Services;

use App\Enums\ModoLibretaArea;
use App\Enums\TipoAsignatura;
use App\Enums\TipoCalificacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAreaLibreta;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAsignaturaPropia;
use App\Models\EstablecimientoModalidadJornada;
use Illuminate\Support\Facades\DB;

class SyncEstablecimientoAsignaturas
{
    /**
     * @param  list<array{
     *     asignatura_id: int|null,
     *     asignatura_propia_clave: string|null,
     *     area_id: int,
     *     grado_id: int,
     *     tipo_asignatura: TipoAsignatura,
     *     aparece_en_libreta: bool,
     *     horas_catalogo: int|null,
     *     tipo_calificacion: TipoCalificacion,
     *     califica_por_destrezas: bool,
     *     usa_tabla_equivalencias: bool,
     *     porcentaje: string|null,
     *     origen: string
     * }>  $filas
     * @param  list<array{
     *     area_id: int,
     *     grado_id: int,
     *     modo_libreta: ModoLibretaArea,
     *     promedio_porcentual: bool
     * }>  $modosArea
     * @param  list<array{
     *     clave: string,
     *     id: int|null,
     *     nombre: string,
     *     area_id: int,
     *     tipo_asignatura: TipoAsignatura,
     *     eliminar: bool
     * }>  $definiciones
     */
    public function handle(
        Establecimiento $establecimiento,
        EstablecimientoModalidadJornada $oferta,
        array $filas,
        array $modosArea = [],
        array $definiciones = [],
    ): void {
        DB::transaction(function () use ($establecimiento, $oferta, $filas, $modosArea, $definiciones): void {
            $anteriores = EstablecimientoAsignatura::query()
                ->where('establecimiento_modalidad_jornada_id', $oferta->id)
                ->get();
            $horasCatalogo = [];
            $horasPropia = [];

            foreach ($anteriores as $anterior) {
                if ($anterior->asignatura_id !== null) {
                    $horasCatalogo[$anterior->asignatura_id][$anterior->grado_id] = $anterior->horas_semanales;
                }

                if ($anterior->asignatura_propia_id !== null) {
                    $horasPropia[$anterior->asignatura_propia_id][$anterior->grado_id] = $anterior->horas_semanales;
                }
            }

            $idPorClave = $this->sincronizarPropias($establecimiento, $oferta, $definiciones);

            EstablecimientoAsignatura::query()
                ->where('establecimiento_modalidad_jornada_id', $oferta->id)
                ->delete();

            EstablecimientoAreaLibreta::query()
                ->where('establecimiento_modalidad_jornada_id', $oferta->id)
                ->delete();

            foreach ($filas as $fila) {
                $propiaId = null;

                if ($fila['asignatura_propia_clave'] !== null) {
                    $propiaId = $idPorClave[$fila['asignatura_propia_clave']] ?? null;

                    if ($propiaId === null) {
                        continue;
                    }
                }

                $horas = $propiaId !== null
                    ? ($horasPropia[$propiaId][$fila['grado_id']] ?? null)
                    : ($horasCatalogo[$fila['asignatura_id']][$fila['grado_id']] ?? $fila['horas_catalogo']);

                EstablecimientoAsignatura::query()->create([
                    'establecimiento_id' => $establecimiento->id,
                    'establecimiento_modalidad_jornada_id' => $oferta->id,
                    'asignatura_id' => $fila['asignatura_id'],
                    'asignatura_propia_id' => $propiaId,
                    'grado_id' => $fila['grado_id'],
                    'aparece_en_libreta' => $fila['aparece_en_libreta'],
                    'horas_semanales' => $horas,
                    'tipo_calificacion' => $fila['tipo_calificacion'],
                    'tipo_asignatura' => $fila['tipo_asignatura'],
                    'califica_por_destrezas' => $fila['califica_por_destrezas'],
                    'usa_tabla_equivalencias' => $fila['usa_tabla_equivalencias'],
                    'porcentaje' => $fila['porcentaje'],
                ]);
            }

            foreach ($modosArea as $modo) {
                EstablecimientoAreaLibreta::query()->create([
                    'establecimiento_id' => $establecimiento->id,
                    'establecimiento_modalidad_jornada_id' => $oferta->id,
                    'area_id' => $modo['area_id'],
                    'grado_id' => $modo['grado_id'],
                    'modo_libreta' => $modo['modo_libreta'],
                    'promedio_porcentual' => $modo['promedio_porcentual'],
                ]);
            }
        });
    }

    /**
     * @param  list<array{
     *     clave: string,
     *     id: int|null,
     *     nombre: string,
     *     area_id: int,
     *     tipo_asignatura: TipoAsignatura,
     *     eliminar: bool
     * }>  $definiciones
     * @return array<string, int>
     */
    private function sincronizarPropias(
        Establecimiento $establecimiento,
        EstablecimientoModalidadJornada $oferta,
        array $definiciones,
    ): array {
        $conservar = [];

        foreach ($definiciones as $definicion) {
            if (! $definicion['eliminar'] && $definicion['id'] !== null) {
                $conservar[] = $definicion['id'];
            }
        }

        $sobrantes = EstablecimientoAsignaturaPropia::query()
            ->where('establecimiento_modalidad_jornada_id', $oferta->id);

        if ($conservar !== []) {
            $sobrantes->whereNotIn('id', $conservar);
        }

        $sobrantes->delete();

        $ids = [];

        foreach ($definiciones as $definicion) {
            if ($definicion['eliminar']) {
                continue;
            }

            $propia = null;

            if ($definicion['id'] !== null) {
                $propia = EstablecimientoAsignaturaPropia::query()
                    ->whereKey($definicion['id'])
                    ->where('establecimiento_modalidad_jornada_id', $oferta->id)
                    ->first();
            }

            $atributos = [
                'establecimiento_id' => $establecimiento->id,
                'establecimiento_modalidad_jornada_id' => $oferta->id,
                'area_id' => $definicion['area_id'],
                'nombre' => $definicion['nombre'],
                'tipo_asignatura' => $definicion['tipo_asignatura'],
            ];

            if ($propia === null) {
                $propia = EstablecimientoAsignaturaPropia::query()->create($atributos);
            } else {
                $propia->update($atributos);
            }

            $ids[$definicion['clave']] = $propia->id;
        }

        return $ids;
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
