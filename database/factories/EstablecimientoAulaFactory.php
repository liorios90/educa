<?php

namespace Database\Factories;

use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoAula>
 */
class EstablecimientoAulaFactory extends Factory
{
    protected $model = EstablecimientoAula::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paralelo' => 'A',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (EstablecimientoAula $aula): void {
            $this->asignarContexto($aula);
        });
    }

    private function asignarContexto(EstablecimientoAula $aula): void
    {
        $grado = $this->gradoPara($aula);
        $periodo = $this->periodoPara($aula, $grado);

        $aula->establecimiento_grado_id = $grado->id;
        $aula->establecimiento_periodo_id = $periodo->id;
        $aula->establecimiento_id = $grado->establecimiento_id;
        $aula->establecimiento_modalidad_jornada_id = $grado->establecimiento_modalidad_jornada_id;
    }

    private function gradoPara(EstablecimientoAula $aula): EstablecimientoGrado
    {
        if ($aula->establecimiento_grado_id) {
            return EstablecimientoGrado::query()->findOrFail($aula->establecimiento_grado_id);
        }

        $atributos = [];

        if ($aula->establecimiento_id) {
            $atributos['establecimiento_id'] = $aula->establecimiento_id;
        }

        if ($aula->establecimiento_modalidad_jornada_id) {
            $atributos['establecimiento_modalidad_jornada_id'] = $aula->establecimiento_modalidad_jornada_id;
        }

        return EstablecimientoGrado::factory()->create($atributos);
    }

    private function periodoPara(EstablecimientoAula $aula, EstablecimientoGrado $grado): EstablecimientoPeriodo
    {
        if ($aula->establecimiento_periodo_id) {
            return EstablecimientoPeriodo::query()->findOrFail($aula->establecimiento_periodo_id);
        }

        return EstablecimientoPeriodo::factory()->activo()->create([
            'establecimiento_id' => $grado->establecimiento_id,
            'establecimiento_modalidad_jornada_id' => $grado->establecimiento_modalidad_jornada_id,
        ]);
    }
}
