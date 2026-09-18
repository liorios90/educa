<?php

namespace Database\Factories;

use App\Models\Sys_Funcion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_Funcion>
 */
class SysFuncionFactory extends Factory
{
    protected $model = Sys_Funcion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(2, true),
            'descripcion' => fake()->sentence(),
            'usuario' => fake()->userName(),
            'activo' => 1,
        ];
    }
}
