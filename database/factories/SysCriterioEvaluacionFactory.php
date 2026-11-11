<?php

namespace Database\Factories;

use App\Models\Sys_Asignatura;
use App\Models\Sys_CriterioEvaluacion;
use App\Models\Sys_Grado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_CriterioEvaluacion>
 */
class SysCriterioEvaluacionFactory extends Factory
{
    protected $model = Sys_CriterioEvaluacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asignatura_id' => Sys_Asignatura::factory(),
            'grado_id' => Sys_Grado::factory(),
            'codigo' => 'CE-'.fake()->unique()->numerify('####'),
            'descripcion' => 'Criterio '.fake()->unique()->sentence(6),
            'orden' => 0,
            'activo' => true,
        ];
    }
}
