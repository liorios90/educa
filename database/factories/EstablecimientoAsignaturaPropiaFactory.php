<?php

namespace Database\Factories;

use App\Enums\TipoAsignatura;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignaturaPropia;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\Sys_Area;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoAsignaturaPropia>
 */
class EstablecimientoAsignaturaPropiaFactory extends Factory
{
    protected $model = EstablecimientoAsignaturaPropia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_id' => Establecimiento::factory(),
            'area_id' => Sys_Area::factory(),
            'nombre' => 'Asignatura '.fake()->unique()->numerify('###'),
            'tipo_asignatura' => TipoAsignatura::Optativa,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (EstablecimientoAsignaturaPropia $propia): void {
            if ($propia->establecimiento_modalidad_jornada_id !== null) {
                return;
            }

            $oferta = $propia->establecimiento_id
                ? EstablecimientoModalidadJornada::factory()->create([
                    'establecimiento_modalidad_id' => EstablecimientoModalidad::factory()->create([
                        'establecimiento_id' => $propia->establecimiento_id,
                    ]),
                ])
                : EstablecimientoModalidadJornada::factory()->create();

            $propia->establecimiento_modalidad_jornada_id = $oferta->id;
            $propia->establecimiento_id = $oferta->establecimientoModalidad->establecimiento_id;
        });
    }
}
