<?php

namespace Database\Factories;

use App\Models\Sys_Zona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_Zona>
 */
class SysZonaFactory extends Factory
{
    protected $model = Sys_Zona::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Zona '.fake()->unique()->numerify('##'),
            'descripcion' => fake()->sentence(),
            'usuario' => fake()->userName(),
            'activo' => 1,
        ];
    }
}
