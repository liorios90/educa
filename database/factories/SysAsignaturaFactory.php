<?php

namespace Database\Factories;

use App\Models\Sys_Area;
use App\Models\Sys_Asignatura;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_Asignatura>
 */
class SysAsignaturaFactory extends Factory
{
    protected $model = Sys_Asignatura::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => fake()->optional()->lexify('????'),
            'nombre' => 'Asignatura '.fake()->unique()->numerify('###'),
            'descripcion' => fake()->optional()->sentence(),
            'orden' => fake()->numberBetween(0, 20),
            'horas_semanales' => fake()->optional()->numberBetween(1, 8),
            'aparece_en_libreta' => true,
            'area_id' => Sys_Area::factory(),
        ];
    }
}
