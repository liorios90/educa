<?php

namespace Database\Factories;

use App\Models\Sys_Nivel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_Nivel>
 */
class SysNivelFactory extends Factory
{
    protected $model = Sys_Nivel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Nivel '.fake()->unique()->numerify('###'),
            'siglas' => fake()->optional()->lexify('??'),
            'descripcion' => fake()->optional()->sentence(),
        ];
    }
}
