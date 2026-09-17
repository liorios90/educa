<?php

namespace Database\Factories;

use App\Models\Sys_Pais;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_Pais>
 */
class SysPaisFactory extends Factory
{
    protected $model = Sys_Pais::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->country(),
            'descripcion' => fake()->sentence(),
            'usuario' => fake()->userName(),
            'activo' => 1,
        ];
    }
}
