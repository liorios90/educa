<?php

namespace Database\Factories;

use App\Enums\ModoLibretaArea;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAreaLibreta;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\Sys_Area;
use App\Models\Sys_Grado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoAreaLibreta>
 */
class EstablecimientoAreaLibretaFactory extends Factory
{
    protected $model = EstablecimientoAreaLibreta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_id' => Establecimiento::factory(),
            'grado_id' => Sys_Grado::factory(),
            'area_id' => Sys_Area::factory(),
            'modo_libreta' => ModoLibretaArea::Asignaturas,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (EstablecimientoAreaLibreta $fila): void {
            if ($fila->establecimiento_modalidad_jornada_id !== null) {
                return;
            }

            $oferta = $fila->establecimiento_id
                ? EstablecimientoModalidadJornada::factory()->create([
                    'establecimiento_modalidad_id' => EstablecimientoModalidad::factory()->create([
                        'establecimiento_id' => $fila->establecimiento_id,
                    ]),
                ])
                : EstablecimientoModalidadJornada::factory()->create();

            $fila->establecimiento_modalidad_jornada_id = $oferta->id;
            $fila->establecimiento_id = $oferta->establecimientoModalidad->establecimiento_id;
        });
    }
}
