<?php

namespace Database\Seeders;

use App\Enums\ModoAsistencia;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoAulasSisEducaSeeder extends Seeder
{
    private const CODIGO_AMIE = '01AS01061';

    /**
     * @var list<string>
     */
    private const GRADOS_CON_DOCENTE = [
        'Quinto de EGB',
        'Octavo de EGB',
        'Primero de Bachillerato',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const MATERIAS_POR_CORREO = [
        'luis.aucapina@siseduca.test' => ['Matemática'],
        'monica.peralta@siseduca.test' => ['Lengua y Literatura'],
        'pablo.guaman@siseduca.test' => ['Ciencias Naturales'],
        'adriana.moscoso@siseduca.test' => ['Estudios Sociales'],
        'ricardo.ortiz@siseduca.test' => ['Educación Física'],
        'gabriela.pesantez@siseduca.test' => ['Inglés'],
        'hugo.calle@siseduca.test' => ['Física'],
        'silvia.astudillo@siseduca.test' => ['Química'],
        'andres.narvaez@siseduca.test' => ['Biología'],
        'cristina.barros@siseduca.test' => ['Educación Cultural y Artística'],
        'marcelo.penafiel@siseduca.test' => ['Historia'],
        'elena.cordero@siseduca.test' => ['Filosofía'],
        'jaime.ugalde@siseduca.test' => ['Emprendimiento y Gestión'],
        'paola.heredia@siseduca.test' => [
            'Educación para la Ciudadanía',
            'Cívica y Acompañamiento Integral en el Aula',
        ],
    ];

    /**
     * Crea los paralelos A y B de cada grado y asigna docentes en Quinto,
     * Octavo y Primero de Bachillerato.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $establecimiento = Establecimiento::query()
                ->where('codigo_amie', self::CODIGO_AMIE)
                ->firstOrFail();
            $periodo = EstablecimientoPeriodo::query()
                ->where('establecimiento_id', $establecimiento->id)
                ->where('activo', true)
                ->firstOrFail();
            $docentes = $this->docentes($establecimiento);
            $grados = EstablecimientoGrado::query()
                ->where('establecimiento_id', $establecimiento->id)
                ->where('establecimiento_modalidad_jornada_id', $periodo->establecimiento_modalidad_jornada_id)
                ->with(['grado', 'subnivel'])
                ->orderBy('grado_id')
                ->get();

            foreach ($grados as $grado) {
                $this->aulasYDocentes($establecimiento, $periodo, $grado, $docentes);
            }
        });
    }

    /**
     * @param  array<string, Empleado>  $docentes
     */
    private function aulasYDocentes(
        Establecimiento $establecimiento,
        EstablecimientoPeriodo $periodo,
        EstablecimientoGrado $grado,
        array $docentes,
    ): void {
        $aulas = [];

        foreach (['A', 'B'] as $paralelo) {
            $aulas[] = EstablecimientoAula::query()->updateOrCreate(
                [
                    'establecimiento_periodo_id' => $periodo->id,
                    'establecimiento_grado_id' => $grado->id,
                    'paralelo' => $paralelo,
                ],
                [
                    'establecimiento_id' => $establecimiento->id,
                    'establecimiento_modalidad_jornada_id' => $periodo->establecimiento_modalidad_jornada_id,
                ],
            );
        }

        $nombreGrado = $grado->grado?->nombre;

        if (! in_array($nombreGrado, self::GRADOS_CON_DOCENTE, true)) {
            return;
        }

        $malla = EstablecimientoAsignatura::query()
            ->with('asignatura')
            ->where('establecimiento_id', $establecimiento->id)
            ->where('establecimiento_modalidad_jornada_id', $periodo->establecimiento_modalidad_jornada_id)
            ->where('grado_id', $grado->grado_id)
            ->orderBy('id')
            ->get();
        $modo = ModoAsistencia::predeterminado($grado->subnivel?->nombre);

        foreach ($malla as $fila) {
            $materia = $fila->asignatura?->nombre;

            if ($materia === null || ! isset($docentes[$materia])) {
                throw new RuntimeException('No hay docente de demostración para '.$materia.' en '.$nombreGrado.'.');
            }

            $docente = $docentes[$materia];

            if ($fila->asignatura_id !== null) {
                $docente->especialidades()->syncWithoutDetaching([$fila->asignatura_id]);
            }

            foreach ($aulas as $aula) {
                EstablecimientoAulaDocente::query()->updateOrCreate(
                    [
                        'establecimiento_aula_id' => $aula->id,
                        'establecimiento_asignatura_id' => $fila->id,
                    ],
                    [
                        'empleado_id' => $docente->id,
                        'modo_asistencia' => $modo,
                    ],
                );
            }
        }
    }

    /**
     * @return array<string, Empleado>
     */
    private function docentes(Establecimiento $establecimiento): array
    {
        $empleados = Empleado::query()
            ->where('activo', 1)
            ->whereHas('persona', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))
            ->with('persona.user')
            ->get()
            ->keyBy(fn (Empleado $empleado): string => (string) $empleado->persona?->user?->email);
        $porMateria = [];

        foreach (self::MATERIAS_POR_CORREO as $correo => $materias) {
            $docente = $empleados->get($correo);

            if ($docente === null) {
                continue;
            }

            foreach ($materias as $materia) {
                $porMateria[$materia] = $docente;
            }
        }

        return $porMateria;
    }
}
