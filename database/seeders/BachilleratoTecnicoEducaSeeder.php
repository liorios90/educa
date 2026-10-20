<?php

namespace Database\Seeders;

use App\Models\Establecimiento;
use App\Models\EstablecimientoAreaLibreta;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoNivel;
use App\Models\EstablecimientoSubnivel;
use App\Models\Sys_Grado;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BachilleratoTecnicoEducaSeeder extends Seeder
{
    private const ESTABLECIMIENTO_CODIGO_AMIE = '17HDEMO01';

    /**
     * Configura el Bachillerato Técnico como opción de BGU, sin crear otro subnivel.
     */
    public function run(): void
    {
        $establecimiento = Establecimiento::query()
            ->where('codigo_amie', self::ESTABLECIMIENTO_CODIGO_AMIE)
            ->first();

        if ($establecimiento === null) {
            throw new RuntimeException('No existe el establecimiento Educa (17HDEMO01). Ejecuta primero DemoInstitutosSeeder.');
        }

        $ofertas = EstablecimientoModalidadJornada::query()
            ->whereHas('establecimientoModalidad', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))
            ->get();

        if ($ofertas->isEmpty()) {
            throw new RuntimeException('El establecimiento Educa no tiene ofertas de modalidad y jornada configuradas.');
        }

        DB::transaction(function () use ($establecimiento, $ofertas): void {
            $this->call(EstructuraEducativaSeeder::class);

            $nivel = Sys_Nivel::query()->where('nombre', 'Bachillerato')->firstOrFail();
            $subnivel = Sys_Subnivel::query()->where('nombre', 'Bachillerato General Unificado')->firstOrFail();
            $grados = $subnivel->grados()->get()->keyBy('nombre');
            $this->exigirGradosBgu($grados);

            $ofertaIds = $ofertas->modelKeys();
            $this->reemplazarSubnivelTecnicoProvisional($establecimiento, $ofertaIds, $subnivel, $grados);

            foreach ($ofertas as $oferta) {
                EstablecimientoNivel::query()->updateOrCreate(
                    [
                        'establecimiento_modalidad_jornada_id' => $oferta->id,
                        'nivel_id' => $nivel->id,
                    ],
                    ['establecimiento_id' => $establecimiento->id],
                );

                EstablecimientoSubnivel::query()->updateOrCreate(
                    [
                        'establecimiento_modalidad_jornada_id' => $oferta->id,
                        'subnivel_id' => $subnivel->id,
                    ],
                    [
                        'establecimiento_id' => $establecimiento->id,
                        'nivel_id' => $nivel->id,
                        'nombre' => $subnivel->nombre,
                    ],
                );

                foreach ($grados as $grado) {
                    EstablecimientoGrado::query()->updateOrCreate(
                        [
                            'establecimiento_modalidad_jornada_id' => $oferta->id,
                            'grado_id' => $grado->id,
                        ],
                        [
                            'establecimiento_id' => $establecimiento->id,
                            'subnivel_id' => $subnivel->id,
                            'nombre' => $grado->nombre,
                        ],
                    );
                }
            }
        });

        $this->command?->info('Bachillerato Técnico usa los cursos de BGU; configura las figuras profesionales y sus módulos en Asignaturas.');
    }

    /**
     * @param  Collection<int, Sys_Grado>  $grados
     */
    private function exigirGradosBgu($grados): void
    {
        foreach (['Primero de Bachillerato', 'Segundo de Bachillerato', 'Tercero de Bachillerato'] as $nombre) {
            if (! $grados->has($nombre)) {
                throw new RuntimeException('El catálogo BGU debe contener Primero, Segundo y Tercero de Bachillerato.');
            }
        }
    }

    /**
     * @param  list<int>  $ofertaIds
     * @param  Collection<int, Sys_Grado>  $gradosBgu
     */
    private function reemplazarSubnivelTecnicoProvisional(
        Establecimiento $establecimiento,
        array $ofertaIds,
        Sys_Subnivel $subnivelBgu,
        $gradosBgu,
    ): void {
        $subnivelTecnico = Sys_Subnivel::query()->where('nombre', 'Bachillerato Técnico')->first();

        if ($subnivelTecnico === null) {
            return;
        }

        $equivalencias = [
            'Primero de Bachillerato Técnico' => 'Primero de Bachillerato',
            'Segundo de Bachillerato Técnico' => 'Segundo de Bachillerato',
            'Tercero de Bachillerato Técnico' => 'Tercero de Bachillerato',
        ];
        $gradosTecnicos = $subnivelTecnico->grados()->get();
        $idsTecnicos = $gradosTecnicos->modelKeys();

        foreach ($gradosTecnicos as $gradoTecnico) {
            $nombreBgu = $equivalencias[$gradoTecnico->nombre] ?? null;

            if ($nombreBgu === null || ! $gradosBgu->has($nombreBgu)) {
                throw new RuntimeException('No se puede convertir automáticamente el grado técnico «'.$gradoTecnico->nombre.'».');
            }

            $gradoBgu = $gradosBgu->get($nombreBgu);
            $filasEstructura = EstablecimientoGrado::query()
                ->whereIn('establecimiento_modalidad_jornada_id', $ofertaIds)
                ->where('grado_id', $gradoTecnico->id)
                ->get();

            if ($filasEstructura->contains(fn (EstablecimientoGrado $fila): bool => $fila->aulas()->exists())) {
                throw new RuntimeException('Hay aulas creadas con los grados técnicos provisionales. Reasigna esas aulas a los cursos BGU antes de ejecutar este seeder.');
            }

            foreach ($filasEstructura as $fila) {
                $gradoExistente = EstablecimientoGrado::query()
                    ->where('establecimiento_modalidad_jornada_id', $fila->establecimiento_modalidad_jornada_id)
                    ->where('grado_id', $gradoBgu->id)
                    ->first();

                if ($gradoExistente !== null) {
                    $fila->delete();
                } else {
                    $fila->update([
                        'subnivel_id' => $subnivelBgu->id,
                        'grado_id' => $gradoBgu->id,
                        'nombre' => $gradoBgu->nombre,
                    ]);
                }
            }

            EstablecimientoAsignatura::query()
                ->whereIn('establecimiento_modalidad_jornada_id', $ofertaIds)
                ->where('grado_id', $gradoTecnico->id)
                ->update(['grado_id' => $gradoBgu->id]);

            EstablecimientoAreaLibreta::query()
                ->whereIn('establecimiento_modalidad_jornada_id', $ofertaIds)
                ->where('grado_id', $gradoTecnico->id)
                ->update(['grado_id' => $gradoBgu->id]);

            DB::table('sys_asignatura_grado')
                ->where('grado_id', $gradoTecnico->id)
                ->get()
                ->each(function (object $fila) use ($gradoBgu, $gradoTecnico): void {
                    DB::table('sys_asignatura_grado')->updateOrInsert(
                        [
                            'asignatura_id' => $fila->asignatura_id,
                            'grado_id' => $gradoBgu->id,
                        ],
                        [
                            'obligatoria' => $fila->obligatoria,
                            'created_at' => $fila->created_at,
                            'updated_at' => now(),
                        ],
                    );

                    DB::table('sys_asignatura_grado')
                        ->where('asignatura_id', $fila->asignatura_id)
                        ->where('grado_id', $gradoTecnico->id)
                        ->delete();
                });

            DB::table('establecimiento_periodo_configuracion_grado_grados')
                ->where('grado_id', $gradoTecnico->id)
                ->get()
                ->each(function (object $fila) use ($gradoBgu, $gradoTecnico): void {
                    DB::table('establecimiento_periodo_configuracion_grado_grados')->updateOrInsert(
                        [
                            'configuracion_grado_id' => $fila->configuracion_grado_id,
                            'grado_id' => $gradoBgu->id,
                        ],
                        [
                            'created_at' => $fila->created_at,
                            'updated_at' => now(),
                        ],
                    );

                    DB::table('establecimiento_periodo_configuracion_grado_grados')
                        ->where('configuracion_grado_id', $fila->configuracion_grado_id)
                        ->where('grado_id', $gradoTecnico->id)
                        ->delete();
                });
        }

        EstablecimientoSubnivel::query()
            ->whereIn('establecimiento_modalidad_jornada_id', $ofertaIds)
            ->where('subnivel_id', $subnivelTecnico->id)
            ->delete();

        $usadoFueraDeEduca = EstablecimientoSubnivel::query()
            ->where('subnivel_id', $subnivelTecnico->id)
            ->exists()
            || EstablecimientoGrado::query()->whereIn('grado_id', $idsTecnicos)->exists()
            || EstablecimientoAsignatura::query()->whereIn('grado_id', $idsTecnicos)->exists()
            || EstablecimientoAreaLibreta::query()->whereIn('grado_id', $idsTecnicos)->exists()
            || DB::table('sys_asignatura_grado')->whereIn('grado_id', $idsTecnicos)->exists();

        if ($usadoFueraDeEduca) {
            throw new RuntimeException('El subnivel provisional Bachillerato Técnico tiene datos fuera de Educa y no se eliminó del catálogo de Sistemas.');
        }

        Sys_Grado::query()->whereIn('id', $idsTecnicos)->delete();

        if ($subnivelTecnico->areas()->exists()) {
            throw new RuntimeException('El subnivel provisional Bachillerato Técnico tiene áreas configuradas y requiere revisión antes de eliminarse.');
        }

        $subnivelTecnico->delete();
    }
}
