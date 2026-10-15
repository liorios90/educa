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
     * @param  list<array{establecimiento_asignatura_id: int, dia_semana: int, hora_inicio: string, hora_fin: string}>  $clases
     */
    public function store(EstablecimientoAula $aula, array $clases): int
    {
        return DB::transaction(function () use ($aula, $clases): int {
            $aulaBloqueada = EstablecimientoAula::query()
                ->whereKey($aula->id)
                ->lockForUpdate()
                ->firstOrFail();

            $malla = EstablecimientoAsignatura::query()
                ->where('establecimiento_id', $aulaBloqueada->establecimiento_id)
                ->where('establecimiento_modalidad_jornada_id', $aulaBloqueada->establecimiento_modalidad_jornada_id)
                ->whereIn('grado_id', function ($query) use ($aulaBloqueada): void {
                    $query->select('grado_id')
                        ->from('establecimiento_grados')
                        ->where('id', $aulaBloqueada->establecimiento_grado_id);
                })
                ->get()
                ->keyBy('id');

            $asignaciones = DB::table('establecimiento_aula_docentes')
                ->where('establecimiento_aula_id', $aulaBloqueada->id)
                ->get()
                ->keyBy('establecimiento_asignatura_id');

            $errores = [];
            $docentePorFila = [];

            foreach ($clases as $indice => $clase) {
                $mallaId = (int) $clase['establecimiento_asignatura_id'];
                $asignacion = $asignaciones->get($mallaId);

                if (! $malla->has($mallaId) || $asignacion === null) {
                    $errores['clases.'.$indice.'.establecimiento_asignatura_id'] = 'Selecciona una asignatura de esta aula que ya tenga docente asignado.';

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
                ->where('establecimiento_aula_id', $aulaBloqueada->id)
                ->get();

            foreach ($clases as $indice => $clase) {
                foreach ($existentes as $existente) {
                    if ((int) $existente->dia_semana === (int) $clase['dia_semana']
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
                    if ((int) $anterior['dia_semana'] === (int) $clase['dia_semana']
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
                    ->where('horario.establecimiento_aula_id', '<>', $aulaBloqueada->id)
                    ->exists();

                if ($choqueDocente) {
                    $errores['clases.'.$indice.'.hora_inicio'] = 'El docente asignado ya tiene otra clase en ese horario.';
                }
            }

            if ($errores !== []) {
                throw ValidationException::withMessages($errores)->errorBag('horario');
            }

            foreach ($clases as $clase) {
                HorarioClase::query()->create([
                    'establecimiento_aula_id' => $aulaBloqueada->id,
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
                ->whereKey($aula->id)
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
