<?php

namespace Database\Seeders;

use App\Enums\ModoLibretaArea;
use App\Enums\TipoAsignatura;
use App\Enums\TipoCalificacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAreaLibreta;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Grado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoAsignaturasSisEducaSeeder extends Seeder
{
    private const CODIGO_AMIE = '01AS01061';

    /**
     * Selecciona todas las asignaturas del catálogo nacional en los grados
     * que ya oferta la Unidad Educativa Sis Educa.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $establecimiento = Establecimiento::query()
                ->where('codigo_amie', self::CODIGO_AMIE)
                ->firstOrFail();

            $ofertas = EstablecimientoModalidadJornada::query()
                ->whereHas('establecimientoModalidad', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))
                ->with(['grados.subnivel'])
                ->get();

            foreach ($ofertas as $oferta) {
                $this->seleccionar($establecimiento, $oferta);
            }
        });
    }

    private function seleccionar(Establecimiento $establecimiento, EstablecimientoModalidadJornada $oferta): void
    {
        $asignaturas = Sys_Asignatura::query()
            ->with(['area', 'gradosCurriculares'])
            ->whereHas('area', fn ($query) => $query->where('es_tecnica', false))
            ->get()
            ->groupBy(fn (Sys_Asignatura $asignatura): int => (int) $asignatura->area?->subnivel_id);

        $areasConVarias = [];

        foreach ($oferta->grados as $grado) {
            foreach ($asignaturas->get($grado->subnivel_id, collect()) as $asignatura) {
                $this->fila($establecimiento, $oferta, $grado, $asignatura);
                $areasConVarias[$asignatura->area_id][$grado->id] = ($areasConVarias[$asignatura->area_id][$grado->id] ?? 0) + 1;
            }
        }

        foreach ($areasConVarias as $areaId => $grados) {
            foreach ($grados as $gradoId => $cantidad) {
                if ($cantidad < 2) {
                    continue;
                }

                EstablecimientoAreaLibreta::query()->firstOrCreate(
                    [
                        'establecimiento_modalidad_jornada_id' => $oferta->id,
                        'grado_id' => $gradoId,
                        'area_id' => $areaId,
                    ],
                    [
                        'establecimiento_id' => $establecimiento->id,
                        'modo_libreta' => ModoLibretaArea::Asignaturas,
                        'promedio_porcentual' => false,
                    ],
                );
            }
        }
    }

    private function fila(
        Establecimiento $establecimiento,
        EstablecimientoModalidadJornada $oferta,
        Sys_Grado $grado,
        Sys_Asignatura $asignatura,
    ): void {
        $curricular = $asignatura->gradosCurriculares->firstWhere('id', $grado->id);
        $tipoCalificacion = $grado->subnivel?->tipo_calificacion ?? TipoCalificacion::Cuantitativa;

        EstablecimientoAsignatura::query()->updateOrCreate(
            [
                'establecimiento_modalidad_jornada_id' => $oferta->id,
                'grado_id' => $grado->id,
                'asignatura_id' => $asignatura->id,
            ],
            [
                'establecimiento_id' => $establecimiento->id,
                'aparece_en_libreta' => $asignatura->aparece_en_libreta,
                'horas_semanales' => $asignatura->horas_semanales,
                'tipo_calificacion' => $tipoCalificacion,
                'tipo_asignatura' => $curricular === null || (bool) $curricular->pivot->obligatoria
                    ? TipoAsignatura::TroncoComun
                    : TipoAsignatura::Optativa,
                'califica_por_destrezas' => $tipoCalificacion === TipoCalificacion::Cualitativa
                    && (bool) $grado->subnivel?->califica_por_destrezas,
                'usa_tabla_equivalencias' => false,
            ],
        );
    }
}
