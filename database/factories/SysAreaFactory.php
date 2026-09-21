<?php

namespace Database\Factories;

use App\Models\Sys_Area;
use App\Models\Sys_Subnivel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_Area>
 */
class SysAreaFactory extends Factory
{
    protected $model = Sys_Area::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => fake()->optional()->lexify('???'),
            'nombre' => 'Área '.fake()->unique()->numerify('###'),
            'descripcion' => fake()->optional()->sentence(),
            'orden' => fake()->numberBetween(0, 20),
            'aparece_en_libreta' => true,
            'subnivel_id' => Sys_Subnivel::factory(),
        ];
    }
}
