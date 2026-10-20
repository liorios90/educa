<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoPeriodo;
use App\Models\Persona;
use App\Services\OrganizacionAcademica;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MatriculasGruposFlotantesSeeder extends Seeder
{
    private const DEMO_SCHOOL_CODE = '17HDEMO01';

    private const DEMO_PERIOD_NAME = 'Periodo demostrativo 2026-2027';

    /**
     * Matricula cuatro alumnos de demostración en dos paralelos del mismo grado.
     */
    public function run(): void
    {
        $establecimiento = Establecimiento::query()
            ->where('codigo_amie', self::DEMO_SCHOOL_CODE)
            ->first();

        if ($establecimiento === null) {
            throw new RuntimeException('No existe el instituto de demostración. Ejecuta primero DemoInstitutosSeeder.');
        }

        $periodo = EstablecimientoPeriodo::query()
            ->where('establecimiento_id', $establecimiento->id)
            ->where('nombre', self::DEMO_PERIOD_NAME)
            ->where('activo', true)
            ->first();

        if ($periodo === null) {
            throw new RuntimeException('No existe el periodo demostrativo activo. Ejecuta primero DemoInstitutosSeeder.');
        }

        $asignaturas = EstablecimientoAsignatura::query()
            ->with(['grado', 'asignatura', 'asignaturaPropia'])
            ->where('establecimiento_modalidad_jornada_id', $periodo->establecimiento_modalidad_jornada_id)
            ->whereNotNull('grado_id')
            ->get()
            ->sortBy(fn (EstablecimientoAsignatura $asignatura): string => ($asignatura->grado?->nombre ?? '').' '.$asignatura->nombre(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $asignaturaInicial = $asignaturas->first();

        if ($asignaturaInicial === null) {
            throw new RuntimeException('El instituto de demostración no tiene asignaturas con grado configuradas.');
        }

        $aulaA = EstablecimientoAula::query()
            ->with('establecimientoGrado.grado')
            ->where('establecimiento_id', $establecimiento->id)
            ->where('establecimiento_periodo_id', $periodo->id)
            ->where('establecimiento_modalidad_jornada_id', $periodo->establecimiento_modalidad_jornada_id)
            ->where('paralelo', 'A')
            ->whereHas('establecimientoGrado', fn ($query) => $query->where('grado_id', $asignaturaInicial->grado_id))
            ->orderBy('establecimiento_grado_id')
            ->first();

        if ($aulaA === null) {
            throw new RuntimeException('El instituto de demostración no tiene aulas A con asignaturas configuradas.');
        }

        DB::transaction(function () use ($aulaA, $establecimiento, $periodo): void {
            $aulaB = EstablecimientoAula::query()->updateOrCreate(
                [
                    'establecimiento_periodo_id' => $periodo->id,
                    'establecimiento_grado_id' => $aulaA->establecimiento_grado_id,
                    'paralelo' => 'B',
                ],
                [
                    'establecimiento_id' => $establecimiento->id,
                    'establecimiento_modalidad_jornada_id' => $periodo->establecimiento_modalidad_jornada_id,
                ],
            );

            $alumnos = $this->alumnosDePrueba($establecimiento);
            $organizacion = app(OrganizacionAcademica::class);
            $organizacion->matricularVarios($aulaA, $alumnos->take(2)->all());
            $organizacion->matricularVarios($aulaB, $alumnos->skip(2)->all());
        });

        $this->command?->info('Cuatro alumnos quedaron matriculados: dos en el paralelo A y dos en el paralelo B de '.$aulaA->establecimientoGrado->grado?->nombre.'.');
    }

    /**
     * @return Collection<int, Alumno>
     */
    private function alumnosDePrueba(Establecimiento $establecimiento): Collection
    {
        return collect(range(1, 4))
            ->map(function (int $numero) use ($establecimiento): Alumno {
                $identificacion = sprintf('GF%08d%02d', $establecimiento->id, $numero);
                $persona = Persona::query()->firstOrCreate(
                    ['identificacion' => $identificacion],
                    Persona::factory()->make([
                        'identificacion' => $identificacion,
                        'establecimiento_id' => $establecimiento->id,
                        'nombres' => sprintf('Alumno flotante %02d', $numero),
                        'apellidos' => 'Datos de prueba',
                    ])->getAttributes(),
                );

                return Alumno::query()->firstOrCreate(
                    ['persona_id' => $persona->id],
                    [
                        'contacto_emergencia' => '0990000000',
                        'usuario' => 'alumno-flotante-'.$establecimiento->id.'-'.$numero,
                        'id_estructura_form_matricula' => 0,
                        'activo' => 1,
                    ],
                );
            });
    }
}
