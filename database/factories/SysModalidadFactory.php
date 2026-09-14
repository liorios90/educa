<?php

namespace Database\Factories;

use App\Models\Sys_Modalidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_Modalidad>
 */
class SysModalidadFactory extends Factory
{
    protected $model = Sys_Modalidad::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Modalidad '.fake()->unique()->numerify('###'),
            'descripcion' => fake()->optional()->sentence(),
        ];
    }
}
