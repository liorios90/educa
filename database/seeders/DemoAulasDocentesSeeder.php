<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoAulasDocentesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $establecimiento = Establecimiento::query()
                ->where('codigo_amie', '01H-F001')
                ->firstOrFail();
            $establecimientoModalidadIds = EstablecimientoModalidad::query()
                ->where('establecimiento_id', $establecimiento->id)
                ->pluck('id');
            $oferta = EstablecimientoModalidadJornada::query()
                ->whereIn('establecimiento_modalidad_id', $establecimientoModalidadIds)
                ->whereHas('periodos', fn ($query) => $query->where('activo', true))
                ->firstOrFail();
            $periodo = EstablecimientoPeriodo::query()
                ->where('establecimiento_modalidad_jornada_id', $oferta->id)
                ->where('activo', true)
                ->firstOrFail();
            $grados = EstablecimientoGrado::query()
                ->where('establecimiento_id', $establecimiento->id)
                ->where('establecimiento_modalidad_jornada_id', $oferta->id)
                ->orderBy('grado_id')
                ->get();
            $docentes = Empleado::query()
                ->where('activo', 1)
                ->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))
                ->whereHas('persona.user', fn ($query) => $query->role(Role::Docente->value))
                ->orderBy('id')
                ->get();

            if ($docentes->isEmpty()) {
                throw new \RuntimeException('Primero genera los docentes de demostración para Demo_Formal.');
            }

            $indiceDocente = 0;

            foreach ($grados as $grado) {
                $aula = EstablecimientoAula::query()->firstOrCreate(
                    [
                        'establecimiento_periodo_id' => $periodo->id,
                        'establecimiento_grado_id' => $grado->id,
                        'paralelo' => 'A',
                    ],
                    [
                        'establecimiento_id' => $establecimiento->id,
                        'establecimiento_modalidad_jornada_id' => $oferta->id,
                    ],
                );

                $asignaturas = EstablecimientoAsignatura::query()
                    ->where('establecimiento_id', $establecimiento->id)
                    ->where('establecimiento_modalidad_jornada_id', $oferta->id)
                    ->where('grado_id', $grado->grado_id)
                    ->orderBy('id')
                    ->get();

                foreach ($asignaturas as $asignatura) {
                    EstablecimientoAulaDocente::query()->firstOrCreate(
                        [
                            'establecimiento_aula_id' => $aula->id,
                            'establecimiento_asignatura_id' => $asignatura->id,
                        ],
                        ['empleado_id' => $docentes[$indiceDocente % $docentes->count()]->id],
                    );
                    $indiceDocente++;
                }
            }
        });
    }
}
