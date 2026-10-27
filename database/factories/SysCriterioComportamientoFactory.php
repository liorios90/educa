<?php

namespace Database\Factories;

use App\Enums\TipoCriterioComportamiento;
use App\Models\Sys_CriterioComportamiento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_CriterioComportamiento>
 */
class SysCriterioComportamientoFactory extends Factory
{
    protected $model = Sys_CriterioComportamiento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => 'TST-'.fake()->unique()->numerify('####'),
            'descripcion' => 'Criterio '.fake()->unique()->words(3, true),
            'tipo' => TipoCriterioComportamiento::Social,
            'activo' => true,
            'obligatorio' => false,
            'usuario' => 'Prueba',
        ];
    }

    public function obligatorio(): static
    {
        return $this->state([
            'obligatorio' => true,
        ]);
    }

    public function inactivo(): static
    {
        return $this->state([
            'activo' => false,
        ]);
    }

    public function cognitivo(): static
    {
        return $this->state([
            'tipo' => TipoCriterioComportamiento::Cognitiva,
        ]);
    }

    public function emocional(): static
    {
        return $this->state([
            'tipo' => TipoCriterioComportamiento::Emocional,
        ]);
    }
}
