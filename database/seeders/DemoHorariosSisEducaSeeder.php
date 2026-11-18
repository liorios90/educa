<?php

namespace Database\Seeders;

use App\Models\Establecimiento;
use App\Models\EstablecimientoAula;
use App\Models\HorarioClase;
use App\Services\HorarioClaseService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoHorariosSisEducaSeeder extends Seeder
{
    private const CODIGO_AMIE = '01AS01061';

    /**
     * Jornada matutina: siete periodos de 45 minutos y un recreo de 15.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const PERIODOS = [
        ['07:00', '07:45'],
        ['07:45', '08:30'],
        ['08:30', '09:15'],
        ['09:30', '10:15'],
        ['10:15', '11:00'],
        ['11:00', '11:45'],
        ['11:45', '12:30'],
    ];

    /**
     * Crea el horario semanal de las aulas que ya tienen docentes.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $establecimiento = Establecimiento::query()
                ->where('codigo_amie', self::CODIGO_AMIE)
                ->firstOrFail();
            $aulas = EstablecimientoAula::query()
                ->where('establecimiento_id', $establecimiento->id)
                ->whereHas('periodo', fn ($query) => $query->where('activo', true))
                ->whereHas('asignaciones')
                ->with(['asignaciones.malla'])
                ->orderBy('establecimiento_grado_id')
                ->orderBy('paralelo')
                ->get();
            $plan = $this->planificar($aulas);
            $servicio = app(HorarioClaseService::class);

            foreach ($aulas as $aula) {
                $clases = $plan[$aula->id] ?? [];

                if ($clases === []) {
                    continue;
                }

                $servicio->store($aula, $clases);
            }
        });
    }

    /**
     * @param  Collection<int, EstablecimientoAula>  $aulas
     * @return array<int, list<array{establecimiento_asignatura_id: int, dia_semana: int, hora_inicio: string, hora_fin: string}>>
     */
    private function planificar(Collection $aulas): array
    {
        $cupos = count(self::PERIODOS) * 5;
        $pedidos = [];
        $docenteDe = [];
        $plan = [];

        foreach ($aulas as $aula) {
            $plan[$aula->id] = [];

            if (HorarioClase::query()->where('establecimiento_aula_id', $aula->id)->exists()) {
                continue;
            }

            foreach ($aula->asignaciones as $asignacion) {
                $horas = max(1, (int) ($asignacion->malla?->horas_semanales ?? 1));
                $pedidos[$aula->id][$asignacion->establecimiento_asignatura_id] = $horas;
                $docenteDe[$aula->id][$asignacion->establecimiento_asignatura_id] = (int) $asignacion->empleado_id;
            }

            $this->recortar($pedidos[$aula->id], $cupos);
        }

        $this->recortarDocentes($pedidos, $docenteDe, $cupos);

        $ocupadoAula = [];
        $ocupadoDocente = [];
        $hoy = [];
        $pendientes = $pedidos;

        for ($dia = 1; $dia <= 5; $dia++) {
            foreach (self::PERIODOS as $periodo => $horas) {
                $docentesDelPeriodo = [];
                $orden = $aulas->sortByDesc(
                    fn (EstablecimientoAula $aula): int => array_sum($pendientes[$aula->id] ?? []),
                )->values();

                foreach ($orden as $aula) {
                    $elegido = $this->elegirMateria(
                        $pendientes[$aula->id] ?? [],
                        $docenteDe[$aula->id] ?? [],
                        $docentesDelPeriodo,
                        $hoy[$aula->id][$dia] ?? [],
                        $pendientes,
                        $docenteDe,
                    );

                    if ($elegido === null) {
                        continue;
                    }

                    $docenteId = $docenteDe[$aula->id][$elegido];
                    $pendientes[$aula->id][$elegido]--;
                    $docentesDelPeriodo[$docenteId] = true;
                    $hoy[$aula->id][$dia][$elegido] = true;
                    $ocupadoAula[$aula->id][$dia][$periodo] = $elegido;
                    $ocupadoDocente[$docenteId][$dia][$periodo] = true;
                    $plan[$aula->id][] = [
                        'establecimiento_asignatura_id' => $elegido,
                        'dia_semana' => $dia,
                        'hora_inicio' => $horas[0],
                        'hora_fin' => $horas[1],
                    ];
                }
            }
        }

        $this->completar($pendientes, $docenteDe, $ocupadoAula, $ocupadoDocente, $plan);

        foreach ($pendientes as $aulaId => $materias) {
            foreach ($materias as $mallaId => $cantidad) {
                if ($cantidad > 0) {
                    throw new RuntimeException('No hubo cupo para todas las horas de la malla '.$mallaId.' en el aula '.$aulaId.'.');
                }
            }
        }

        return $plan;
    }

    /**
     * @param  array<int, int>  $pedidos
     */
    private function recortar(array &$pedidos, int $cupos): void
    {
        while (array_sum($pedidos) > $cupos) {
            $mallaId = $this->mayor($pedidos);

            if ($mallaId === null) {
                throw new RuntimeException('Las asignaturas no caben en la jornada matutina.');
            }

            $pedidos[$mallaId]--;
        }
    }

    /**
     * @param  array<int, array<int, int>>  $pedidos
     * @param  array<int, array<int, int>>  $docenteDe
     */
    private function recortarDocentes(array &$pedidos, array $docenteDe, int $cupos): void
    {
        $carga = [];

        foreach ($pedidos as $aulaId => $materias) {
            foreach ($materias as $mallaId => $cantidad) {
                $docenteId = $docenteDe[$aulaId][$mallaId];
                $carga[$docenteId] = ($carga[$docenteId] ?? 0) + $cantidad;
            }
        }

        foreach ($carga as $docenteId => $total) {
            while ($total > $cupos) {
                $elegido = null;
                $maximo = 1;

                foreach ($pedidos as $aulaId => $materias) {
                    foreach ($materias as $mallaId => $cantidad) {
                        if ($docenteDe[$aulaId][$mallaId] === $docenteId && $cantidad > $maximo) {
                            $maximo = $cantidad;
                            $elegido = [$aulaId, $mallaId];
                        }
                    }
                }

                if ($elegido === null) {
                    throw new RuntimeException('Un docente supera las horas de la jornada matutina.');
                }

                $pedidos[$elegido[0]][$elegido[1]]--;
                $total--;
            }
        }
    }

    /**
     * @param  array<int, int>  $materias
     * @param  array<int, int>  $docentes
     * @param  array<int, bool>  $ocupados
     * @param  array<int, bool>  $yaHoy
     * @param  array<int, array<int, int>>  $pendientes
     * @param  array<int, array<int, int>>  $docenteDe
     */
    private function elegirMateria(array $materias, array $docentes, array $ocupados, array $yaHoy, array $pendientes, array $docenteDe): ?int
    {
        $elegido = null;
        $mejor = null;

        foreach ($materias as $mallaId => $cantidad) {
            if ($cantidad < 1 || isset($ocupados[$docentes[$mallaId]])) {
                continue;
            }

            $carga = 0;

            foreach ($pendientes as $aulaId => $pedido) {
                foreach ($pedido as $otraMalla => $horas) {
                    if ($docenteDe[$aulaId][$otraMalla] === $docentes[$mallaId]) {
                        $carga += $horas;
                    }
                }
            }

            $puntaje = [$carga, isset($yaHoy[$mallaId]) ? 0 : 1, $cantidad, -$mallaId];

            if ($mejor === null || $puntaje > $mejor) {
                $mejor = $puntaje;
                $elegido = $mallaId;
            }
        }

        return $elegido;
    }

    /**
     * @param  array<int, array<int, int>>  $pendientes
     * @param  array<int, array<int, int>>  $docenteDe
     * @param  array<int, array<int, array<int, int>>>  $ocupadoAula
     * @param  array<int, array<int, array<int, bool>>>  $ocupadoDocente
     * @param  array<int, list<array{establecimiento_asignatura_id: int, dia_semana: int, hora_inicio: string, hora_fin: string}>>  $plan
     */
    private function completar(array &$pendientes, array $docenteDe, array &$ocupadoAula, array &$ocupadoDocente, array &$plan): void
    {
        foreach ($pendientes as $aulaId => $materias) {
            foreach ($materias as $mallaId => $cantidad) {
                for ($numero = 0; $numero < $cantidad; $numero++) {
                    if ($this->ubicar($aulaId, $mallaId, $docenteDe[$aulaId][$mallaId], $ocupadoAula, $ocupadoDocente, $plan)) {
                        $pendientes[$aulaId][$mallaId]--;
                    }
                }
            }
        }
    }

    /**
     * @param  array<int, array<int, array<int, int>>>  $ocupadoAula
     * @param  array<int, array<int, array<int, bool>>>  $ocupadoDocente
     * @param  array<int, list<array{establecimiento_asignatura_id: int, dia_semana: int, hora_inicio: string, hora_fin: string}>>  $plan
     */
    private function ubicar(int $aulaId, int $mallaId, int $docenteId, array &$ocupadoAula, array &$ocupadoDocente, array &$plan): bool
    {
        for ($dia = 1; $dia <= 5; $dia++) {
            foreach (self::PERIODOS as $periodo => $horas) {
                if (isset($ocupadoAula[$aulaId][$dia][$periodo]) || isset($ocupadoDocente[$docenteId][$dia][$periodo])) {
                    continue;
                }

                $ocupadoAula[$aulaId][$dia][$periodo] = $mallaId;
                $ocupadoDocente[$docenteId][$dia][$periodo] = true;
                $plan[$aulaId][] = [
                    'establecimiento_asignatura_id' => $mallaId,
                    'dia_semana' => $dia,
                    'hora_inicio' => $horas[0],
                    'hora_fin' => $horas[1],
                ];

                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, int>  $cantidades
     */
    private function mayor(array $cantidades): ?int
    {
        $elegido = null;
        $maximo = 1;

        foreach ($cantidades as $id => $cantidad) {
            if ($cantidad > $maximo) {
                $maximo = $cantidad;
                $elegido = $id;
            }
        }

        return $elegido;
    }
}
