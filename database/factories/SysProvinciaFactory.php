<?php

namespace Database\Factories;

use App\Models\Sys_Pais;
use App\Models\Sys_Provincia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_Provincia>
 */
class SysProvinciaFactory extends Factory
{
    protected $model = Sys_Provincia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->state(),
            'descripcion' => fake()->sentence(),
            'usuario' => fake()->userName(),
            'activo' => 1,
            'pais_id' => Sys_Pais::factory(),
        ];
    }
}
