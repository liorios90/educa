<?php

namespace Database\Factories;

use App\Models\Sys_Asignatura;
use App\Models\Sys_Destreza;
use App\Models\Sys_Grado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_Destreza>
 */
class SysDestrezaFactory extends Factory
{
    protected $model = Sys_Destreza::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asignatura_id' => Sys_Asignatura::factory(),
            'grado_id' => Sys_Grado::factory(),
            'codigo' => 'D-'.fake()->unique()->numerify('####'),
            'descripcion' => 'Destreza '.fake()->unique()->sentence(4),
            'imprescindible' => true,
            'orden' => 0,
            'activo' => true,
        ];
    }
}
