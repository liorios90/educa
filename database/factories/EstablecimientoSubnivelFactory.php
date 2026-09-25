<?php

namespace Database\Factories;

use App\Models\Establecimiento;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoSubnivel;
use App\Models\Sys_Subnivel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoSubnivel>
 */
class EstablecimientoSubnivelFactory extends Factory
{
    protected $model = EstablecimientoSubnivel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subnivel = Sys_Subnivel::factory()->create();

        return [
            'establecimiento_id' => Establecimiento::factory(),
            'nivel_id' => $subnivel->nivel_id,
            'subnivel_id' => $subnivel->id,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (EstablecimientoSubnivel $subnivel): void {
            $this->asignarOferta($subnivel);
        });
    }

    private function asignarOferta(EstablecimientoSubnivel $subnivel): void
    {
        if ($subnivel->establecimiento_modalidad_jornada_id !== null) {
            return;
        }

        $oferta = $this->ofertaPara($subnivel->establecimiento_id);
        $subnivel->establecimiento_modalidad_jornada_id = $oferta->id;
        $subnivel->establecimiento_id = $oferta->establecimientoModalidad->establecimiento_id;
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
