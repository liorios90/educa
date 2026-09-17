<?php

namespace Database\Factories;

use App\Models\Padre;
use App\Models\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Padre>
 */
class PadreFactory extends Factory
{
    protected $model = Padre::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'persona_id' => Persona::factory(),
            'estado_civil_id' => 'casado',
            'vive_con_estudiante' => 1,
            'titulo' => 'Ingeniero',
            'usuario' => fake()->userName(),
            'activo' => 1,
        ];
    }
}
