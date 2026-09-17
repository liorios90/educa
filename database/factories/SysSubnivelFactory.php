<?php

namespace Database\Factories;

use App\Enums\TipoCalificacion;
use App\Models\Sys_Nivel;
use App\Models\Sys_Subnivel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sys_Subnivel>
 */
class SysSubnivelFactory extends Factory
{
    protected $model = Sys_Subnivel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Subnivel '.fake()->unique()->numerify('###'),
            'siglas' => fake()->optional()->lexify('??'),
            'descripcion' => fake()->optional()->sentence(),
            'tipo_calificacion' => fake()->randomElement(TipoCalificacion::cases()),
            'nivel_id' => Sys_Nivel::factory(),
        ];
    }
}
