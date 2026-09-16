<?php

namespace Database\Factories;

use App\Models\Sys_Distrito;
use App\Models\Sys_Zona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_Distrito>
 */
class SysDistritoFactory extends Factory
{
    protected $model = Sys_Distrito::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Distrito '.fake()->unique()->numerify('###'),
            'provincia' => fake()->state(),
            'descripcion' => fake()->sentence(),
            'usuario' => fake()->userName(),
            'activo' => 1,
            'zona_id' => Sys_Zona::factory(),
        ];
    }
}
