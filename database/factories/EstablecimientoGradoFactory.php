<?php

namespace Database\Factories;

use App\Models\Establecimiento;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\Sys_Grado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoGrado>
 */
class EstablecimientoGradoFactory extends Factory
{
    protected $model = EstablecimientoGrado::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grado = Sys_Grado::factory()->create();

        return [
            'establecimiento_id' => Establecimiento::factory(),
            'subnivel_id' => $grado->subnivel_id,
            'grado_id' => $grado->id,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (EstablecimientoGrado $grado): void {
            $this->asignarOferta($grado);
        });
    }

    private function asignarOferta(EstablecimientoGrado $grado): void
    {
        if ($grado->establecimiento_modalidad_jornada_id !== null) {
            return;
        }

        $oferta = $this->ofertaPara($grado->establecimiento_id);
        $grado->establecimiento_modalidad_jornada_id = $oferta->id;
        $grado->establecimiento_id = $oferta->establecimientoModalidad->establecimiento_id;
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
