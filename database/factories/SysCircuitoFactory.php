<?php

namespace Database\Factories;

use App\Models\Sys_Circuito;
use App\Models\Sys_Distrito;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_Circuito>
 */
class SysCircuitoFactory extends Factory
{
    protected $model = Sys_Circuito::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Circuito '.fake()->unique()->numerify('###'),
            'descripcion' => fake()->sentence(),
            'usuario' => fake()->userName(),
            'activo' => 1,
            'distrito_id' => Sys_Distrito::factory(),
        ];
    }
}
