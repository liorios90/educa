<?php

namespace Database\Factories;

use App\Models\Sys_Grado;
use App\Models\Sys_Subnivel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_Grado>
 */
class SysGradoFactory extends Factory
{
    protected $model = Sys_Grado::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Grado '.fake()->unique()->numerify('###'),
            'siglas' => fake()->optional()->lexify('??'),
            'descripcion' => fake()->optional()->sentence(),
            'subnivel_id' => Sys_Subnivel::factory(),
        ];
    }
}
