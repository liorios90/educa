<?php

namespace Database\Factories;

use App\Models\Sys_Destreza;
use App\Models\Sys_IndicadorEvaluacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_IndicadorEvaluacion>
 */
class SysIndicadorEvaluacionFactory extends Factory
{
    protected $model = Sys_IndicadorEvaluacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'destreza_id' => Sys_Destreza::factory(),
            'codigo' => 'I-'.fake()->unique()->numerify('####'),
            'descripcion' => 'Indicador '.fake()->unique()->sentence(6),
            'orden' => 0,
            'activo' => true,
        ];
    }
}
