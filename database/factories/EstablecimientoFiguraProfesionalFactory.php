<?php

namespace Database\Factories;

use App\Models\Establecimiento;
use App\Models\EstablecimientoFiguraProfesional;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoFiguraProfesional>
 */
class EstablecimientoFiguraProfesionalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_id' => Establecimiento::factory(),
            'nombre' => fake()->unique()->jobTitle(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (EstablecimientoFiguraProfesional $figura): void {
            if ($figura->establecimiento_modalidad_jornada_id !== null) {
                return;
            }

            $modalidad = EstablecimientoModalidad::factory()->create([
                'establecimiento_id' => $figura->establecimiento_id,
            ]);
            $oferta = EstablecimientoModalidadJornada::factory()->create([
                'establecimiento_modalidad_id' => $modalidad->id,
            ]);
            $figura->establecimiento_modalidad_jornada_id = $oferta->id;
        });
    }
}
