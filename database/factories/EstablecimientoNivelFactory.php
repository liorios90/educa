<?php

namespace Database\Factories;

use App\Models\Establecimiento;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoNivel;
use App\Models\Sys_Nivel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoNivel>
 */
class EstablecimientoNivelFactory extends Factory
{
    protected $model = EstablecimientoNivel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_id' => Establecimiento::factory(),
            'nivel_id' => Sys_Nivel::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (EstablecimientoNivel $nivel): void {
            $this->asignarOferta($nivel);
        });
    }

    private function asignarOferta(EstablecimientoNivel $nivel): void
    {
        if ($nivel->establecimiento_modalidad_jornada_id !== null) {
            return;
        }

        $oferta = $this->ofertaPara($nivel->establecimiento_id);
        $nivel->establecimiento_modalidad_jornada_id = $oferta->id;
        $nivel->establecimiento_id = $oferta->establecimientoModalidad->establecimiento_id;
    }

    private function ofertaPara(mixed $establecimientoId): EstablecimientoModalidadJornada
    {
        if ($establecimientoId) {
            return EstablecimientoModalidadJornada::factory()->create([
                'establecimiento_modalidad_id' => EstablecimientoModalidad::factory()->create([
                    'establecimiento_id' => $establecimientoId,
                ]),
            ]);
        }

        return EstablecimientoModalidadJornada::factory()->create();
    }
}
