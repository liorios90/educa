<?php

namespace Database\Factories;

use App\Models\Sys_Jornada;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_Jornada>
 */
class SysJornadaFactory extends Factory
{
    protected $model = Sys_Jornada::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Jornada '.fake()->unique()->numerify('###'),
            'descripcion' => fake()->optional()->sentence(),
        ];
    }
}
