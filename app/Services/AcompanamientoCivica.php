<?php

namespace App\Services;

use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoHabilidadCivica;
use Illuminate\Support\Facades\DB;

/**
 * Periodo pedagógico de Cívica y Acompañamiento Integral en el Aula.
 *
 * El Ministerio pide evaluar nueve habilidades: cuatro obligatorias y cinco
 * elegidas por la institución. Cada una conserva su letra. No se promedian
 * ni se reemplazan por la más baja.
 */
class AcompanamientoCivica
{
    public const ASIGNATURA = 'Cívica y Acompañamiento Integral en el Aula';

    /**
     * @var list<string>
     */
    public const OBLIGATORIAS = ['CIV.01', 'CIV.02', 'CIV.04', 'CIV.11'];

    /**
     * @var list<string>
     */
    public const COGNITIVAS = ['CIV.03', 'CIV.05'];

    /**
     * @var list<string>
     */
    public const SOCIALES = ['CIV.06', 'CIV.07', 'CIV.08', 'CIV.09', 'CIV.10', 'CIV.12', 'CIV.13'];

    /**
     * @var list<string>
     */
    public const EMOCIONALES = ['CIV.14', 'CIV.15'];

    /**
     * @return list<array{codigo: string, nombre: string}>
     */
    public function obligatorias(): array
    {
        return [
            ['codigo' => 'CIV.01', 'nombre' => 'Autoconocimiento'],
            ['codigo' => 'CIV.02', 'nombre' => 'Pensamiento crítico'],
            ['codigo' => 'CIV.04', 'nombre' => 'Toma de decisiones'],
            ['codigo' => 'CIV.11', 'nombre' => 'Manejo de conflictos'],
        ];
    }

    /**
     * @return array{
     *     cognitivas: list<array{codigo: string, nombre: string}>,
     *     sociales: list<array{codigo: string, nombre: string}>,
     *     emocionales: list<array{codigo: string, nombre: string}>
     * }
     */
    public function opciones(): array
    {
        return [
            'cognitivas' => [
                ['codigo' => 'CIV.03', 'nombre' => 'Manejo de problemas'],
                ['codigo' => 'CIV.05', 'nombre' => 'Pensamiento creativo'],
            ],
            'sociales' => [
                ['codigo' => 'CIV.06', 'nombre' => 'Trabajo en equipo'],
                ['codigo' => 'CIV.07', 'nombre' => 'Conciencia social'],
                ['codigo' => 'CIV.08', 'nombre' => 'Pensamiento ético'],
                ['codigo' => 'CIV.09', 'nombre' => 'Empatía'],
                ['codigo' => 'CIV.10', 'nombre' => 'Relaciones interpersonales'],
                ['codigo' => 'CIV.12', 'nombre' => 'Comunicación efectiva'],
                ['codigo' => 'CIV.13', 'nombre' => 'Conciencia global'],
            ],
            'emocionales' => [
                ['codigo' => 'CIV.14', 'nombre' => 'Manejo de emociones y sentimientos'],
                ['codigo' => 'CIV.15', 'nombre' => 'Manejo del estrés y las tensiones'],
            ],
        ];
    }

    public function esMalla(EstablecimientoAsignatura $asignatura): bool
    {
        $asignatura->loadMissing('asignatura');

        return $asignatura->asignatura?->nombre === self::ASIGNATURA;
    }

    /**
     * @return list<string>
     */
    public function codigosElegidos(int $establecimientoId): array
    {
        return EstablecimientoHabilidadCivica::query()
            ->where('establecimiento_id', $establecimientoId)
            ->orderBy('codigo')
            ->pluck('codigo')
            ->all();
    }

    /**
     * @param  list<string>  $codigos
     */
    public function seleccionValida(array $codigos): bool
    {
        $codigos = array_values(array_unique($codigos));
        $permitidas = [...self::COGNITIVAS, ...self::SOCIALES, ...self::EMOCIONALES];

        return count($codigos) === 5
            && count(array_intersect($codigos, self::COGNITIVAS)) === 1
            && count(array_intersect($codigos, self::SOCIALES)) === 3
            && count(array_intersect($codigos, self::EMOCIONALES)) === 1
            && array_diff($codigos, $permitidas) === [];
    }

    public function seleccionCompleta(int $establecimientoId): bool
    {
        return $this->seleccionValida($this->codigosElegidos($establecimientoId));
    }

    /**
     * Habilidades que el docente puede calificar en este establecimiento.
     *
     * @return list<string>
     */
    public function codigosEvaluables(int $establecimientoId): array
    {
        $elegidos = $this->codigosElegidos($establecimientoId);

        if (! $this->seleccionValida($elegidos)) {
            return self::OBLIGATORIAS;
        }

        return [...self::OBLIGATORIAS, ...$elegidos];
    }

    /**
     * @param  list<string>  $codigos
     */
    public function guardar(Establecimiento $establecimiento, array $codigos): void
    {
        DB::transaction(function () use ($establecimiento, $codigos): void {
            $establecimiento->habilidadesCivica()->delete();

            foreach (array_values(array_unique($codigos)) as $codigo) {
                $establecimiento->habilidadesCivica()->create([
                    'codigo' => $codigo,
                ]);
            }
        });
    }
}
