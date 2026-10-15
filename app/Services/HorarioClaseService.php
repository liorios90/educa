<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\HorarioClase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HorarioClaseService
{
    /**
     * @param  list<array{establecimiento_aula_id: int, establecimiento_asignatura_id: int, dia_semana: int, hora_inicio: string, hora_fin: string}>  $clases
     */
    public function store(EstablecimientoAula $aula, array $clases): int
    {
        return DB::transaction(function () use ($aula, $clases): int {
            $aulaBloqueada = EstablecimientoAula::query()
                ->whereKey($aula->id)
                ->lockForUpdate()
                ->firstOrFail();

            $aulaIds = collect($clases)
                ->pluck('establecimiento_aula_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();
            $aulas = EstablecimientoAula::query()
                ->with('establecimientoGrado')
                ->whereIn('id', $aulaIds)
                ->where('establecimiento_id', $aulaBloqueada->establecimiento_id)
                ->where('establecimiento_modalidad_jornada_id', $aulaBloqueada->establecimiento_modalidad_jornada_id)
                ->where('establecimiento_periodo_id', $aulaBloqueada->establecimiento_periodo_id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($aulas->count() !== $aulaIds->count()) {
                throw ValidationException::withMessages([
                    'clases' => 'Selecciona aulas activas de esta institución, modalidad y periodo.',
                ])->errorBag('horario');
            }

            $malla = EstablecimientoAsignatura::query()
                ->where('establecimiento_id', $aulaBloqueada->establecimiento_id)
                ->where('establecimiento_modalidad_jornada_id', $aulaBloqueada->establecimiento_modalidad_jornada_id)
                ->whereIn('id', collect($clases)->pluck('establecimiento_asignatura_id')->unique())
                ->get()
                ->keyBy('id');

            $asignaciones = DB::table('establecimiento_aula_docentes')
                ->whereIn('establecimiento_aula_id', $aulaIds)
                ->whereIn('establecimiento_asignatura_id', $malla->keys())
                ->get()
                ->keyBy(fn (object $asignacion): string => $asignacion->establecimiento_aula_id.':'.$asignacion->establecimiento_asignatura_id);

            $errores = [];
            $docentePorFila = [];

            foreach ($clases as $indice => $clase) {
                $mallaId = (int) $clase['establecimiento_asignatura_id'];
                $aulaId = (int) $clase['establecimiento_aula_id'];
                $asignacion = $asignaciones->get($aulaId.':'.$mallaId);

                if (! $malla->has($mallaId)
                    || $asignacion === null
                    || (int) $malla->get($mallaId)->grado_id !== (int) $aulas->get($aulaId)?->establecimientoGrado?->grado_id) {
                    $errores['clases.'.$indice.'.establecimiento_asignatura_id'] = 'Selecciona una asignatura que tenga docente asignado en el aula elegida.';

                    continue;
                }

                $docentePorFila[$indice] = (int) $asignacion->empleado_id;
            }

            if ($errores !== []) {
                throw ValidationException::withMessages($errores)->errorBag('horario');
            }

            $docenteIds = array_values(array_unique($docentePorFila));

            Empleado::query()
                ->whereIn('id', $docenteIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            $existentes = HorarioClase::query()
                ->whereIn('establecimiento_aula_id', $aulaIds)
                ->get();

            foreach ($clases as $indice => $clase) {
                foreach ($existentes as $existente) {
                    if ((int) $existente->establecimiento_aula_id === (int) $clase['establecimiento_aula_id']
                        && (int) $existente->dia_semana === (int) $clase['dia_semana']
                        && $this->seCruzan(
                            $clase['hora_inicio'],
                            $clase['hora_fin'],
                            $existente->hora_inicio,
                            $existente->hora_fin,
                        )) {
                        $errores['clases.'.$indice.'.hora_inicio'] = 'Ese horario se cruza con otra clase de esta aula.';

                        break;
                    }
                }

                foreach (array_slice($clases, 0, $indice, true) as $anteriorIndice => $anterior) {
                    if ((int) $anterior['establecimiento_aula_id'] === (int) $clase['establecimiento_aula_id']
                        && (int) $anterior['dia_semana'] === (int) $clase['dia_semana']
                        && $this->seCruzan(
                            $clase['hora_inicio'],
                            $clase['hora_fin'],
                            $anterior['hora_inicio'],
                            $anterior['hora_fin'],
                        )) {
                        $errores['clases.'.$indice.'.hora_inicio'] = 'Las clases de esta aula no pueden cruzarse entre sí.';
                        $errores['clases.'.$anteriorIndice.'.hora_inicio'] = 'Las clases de esta aula no pueden cruzarse entre sí.';
                    }
                }

                $choqueDocente = DB::table('horario_clases as horario')
                    ->join('establecimiento_aulas as aula', 'aula.id', '=', 'horario.establecimiento_aula_id')
                    ->join('establecimiento_aula_docentes as asignacion', function ($join): void {
                        $join->on('asignacion.establecimiento_aula_id', '=', 'horario.establecimiento_aula_id')
                            ->on('asignacion.establecimiento_asignatura_id', '=', 'horario.establecimiento_asignatura_id');
                    })
                    ->where('aula.establecimiento_periodo_id', $aulaBloqueada->establecimiento_periodo_id)
                    ->where('asignacion.empleado_id', $docentePorFila[$indice])
                    ->where('horario.dia_semana', $clase['dia_semana'])
                    ->where('horario.hora_inicio', '<', $clase['hora_fin'])
                    ->where('horario.hora_fin', '>', $clase['hora_inicio'])
                    ->exists();

                if ($choqueDocente) {
                    $errores['clases.'.$indice.'.hora_inicio'] = 'El docente asignado ya tiene otra clase en ese horario.';
                }

                foreach (array_slice($clases, 0, $indice, true) as $anteriorIndice => $anterior) {
                    if (($docentePorFila[$anteriorIndice] ?? null) === $docentePorFila[$indice]
                        && (int) $anterior['dia_semana'] === (int) $clase['dia_semana']
                        && $this->seCruzan($clase['hora_inicio'], $clase['hora_fin'], $anterior['hora_inicio'], $anterior['hora_fin'])) {
                        $errores['clases.'.$indice.'.hora_inicio'] = 'El docente no puede tener dos clases al mismo tiempo.';
                    }
                }
            }

            if ($errores !== []) {
                throw ValidationException::withMessages($errores)->errorBag('horario');
            }

            foreach ($clases as $clase) {
                HorarioClase::query()->create([
                    'establecimiento_aula_id' => (int) $clase['establecimiento_aula_id'],
                    'establecimiento_asignatura_id' => (int) $clase['establecimiento_asignatura_id'],
                    'dia_semana' => (int) $clase['dia_semana'],
                    'hora_inicio' => $clase['hora_inicio'],
                    'hora_fin' => $clase['hora_fin'],
                ]);
            }

            return count($clases);
        }, attempts: 3);
    }

    /**
     * @param  array{dia_semana: int, hora_inicio: string, hora_fin: string}  $clase
     */
    public function update(EstablecimientoAula $aula, HorarioClase $horario, array $clase): void
    {
        DB::transaction(function () use ($aula, $horario, $clase): void {
            $aulaBloqueada = EstablecimientoAula::query()
                ->whereKey($horario->establecimiento_aula_id)
                ->where('establecimiento_id', $aula->establecimiento_id)
                ->where('establecimiento_modalidad_jornada_id', $aula->establecimiento_modalidad_jornada_id)
                ->where('establecimiento_periodo_id', $aula->establecimiento_periodo_id)
                ->lockForUpdate()
                ->firstOrFail();
            $horarioBloqueado = HorarioClase::query()
                ->whereKey($horario->id)
                ->where('establecimiento_aula_id', $aulaBloqueada->id)
                ->lockForUpdate()
                ->firstOrFail();

            $asignacion = DB::table('establecimiento_aula_docentes')
                ->where('establecimiento_aula_id', $aulaBloqueada->id)
                ->where('establecimiento_asignatura_id', $horarioBloqueado->establecimiento_asignatura_id)
                ->first();

            if ($asignacion === null) {
                throw ValidationException::withMessages([
                    'hora_inicio' => 'La asignatura debe tener un docente asignado antes de moverla.',
                ])->errorBag('horario');
            }

            Empleado::query()
                ->whereKey($asignacion->empleado_id)
                ->lockForUpdate()
                ->firstOrFail();

            $cruceEnAula = HorarioClase::query()
                ->where('establecimiento_aula_id', $aulaBloqueada->id)
                ->whereKeyNot($horarioBloqueado->id)
                ->where('dia_semana', $clase['dia_semana'])
                ->where('hora_inicio', '<', $clase['hora_fin'])
                ->where('hora_fin', '>', $clase['hora_inicio'])
                ->exists();

            if ($cruceEnAula) {
                throw ValidationException::withMessages([
                    'hora_inicio' => 'Ese horario se cruza con otra clase de esta aula.',
                ])->errorBag('horario');
            }

            $cruceDocente = DB::table('horario_clases as horario')
                ->join('establecimiento_aulas as aula', 'aula.id', '=', 'horario.establecimiento_aula_id')
                ->join('establecimiento_aula_docentes as asignacion', function ($join): void {
                    $join->on('asignacion.establecimiento_aula_id', '=', 'horario.establecimiento_aula_id')
                        ->on('asignacion.establecimiento_asignatura_id', '=', 'horario.establecimiento_asignatura_id');
                })
                ->where('aula.establecimiento_periodo_id', $aulaBloqueada->establecimiento_periodo_id)
                ->where('asignacion.empleado_id', $asignacion->empleado_id)
                ->where('horario.id', '<>', $horarioBloqueado->id)
                ->where('horario.dia_semana', $clase['dia_semana'])
                ->where('horario.hora_inicio', '<', $clase['hora_fin'])
                ->where('horario.hora_fin', '>', $clase['hora_inicio'])
                ->exists();

            if ($cruceDocente) {
                throw ValidationException::withMessages([
                    'hora_inicio' => 'El docente asignado ya tiene otra clase en ese horario.',
                ])->errorBag('horario');
            }

            $horarioBloqueado->update($clase);
        }, attempts: 3);
    }

    private function seCruzan(string $inicio, string $fin, string $otroInicio, string $otroFin): bool
    {
        return $inicio < $otroFin && $fin > $otroInicio;
    }
}
