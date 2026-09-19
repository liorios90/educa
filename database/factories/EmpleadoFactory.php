<?php

namespace Database\Factories;

use App\Models\Empleado;
use App\Models\Persona;
use App\Models\Sys_TipoContrato;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empleado>
 */
class EmpleadoFactory extends Factory
{
    protected $model = Empleado::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'persona_id' => Persona::factory(),
            'tipo_contrato_id' => Sys_TipoContrato::factory(),
            'cargo_id' => null,
            'funcion_id' => null,
            'horas' => 40,
            'anios_experiencia' => 5,
            'anios_instituto' => 2,
            'contacto_emergencia' => fake()->name(),
            'contacto_num' => fake()->numerify('09########'),
            'usuario' => fake()->userName(),
            'activo' => 1,
        ];
    }
}
