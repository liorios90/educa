<?php

namespace Database\Factories;

use App\Models\Sys_TipoContrato;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_TipoContrato>
 */
class SysTipoContratoFactory extends Factory
{
    protected $model = Sys_TipoContrato::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('TC-##'),
            'nombre' => fake()->unique()->words(2, true),
            'descripcion' => fake()->sentence(),
            'usuario' => fake()->userName(),
            'activo' => 1,
        ];
    }
}
