<?php

namespace Database\Factories;

use App\Models\Establecimiento;
use App\Models\Sys_Circuito;
use App\Models\Sys_Distrito;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Establecimiento>
 */
class EstablecimientoFactory extends Factory
{
    protected $model = Establecimiento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $circuito = Sys_Circuito::factory()->create();
        $zonaId = Sys_Distrito::query()->whereKey($circuito->distrito_id)->value('zona_id');

        return [
            'nombre' => 'Unidad educativa '.fake()->unique()->numerify('###'),
            'descripcion' => fake()->sentence(),
            'direccion' => fake()->streetAddress(),
            'telefono' => fake()->numerify('02######'),
            'representante' => fake()->name(),
            'codigo_amie' => fake()->unique()->bothify('17H#####'),
            'regimen' => 'Sierra',
            'email' => fake()->unique()->safeEmail(),
            'usuario' => fake()->userName(),
            'activo' => 1,
            'logo' => 'logo.png',
            'zona_id' => $zonaId,
            'distrito_id' => $circuito->distrito_id,
            'circuito_id' => $circuito->id,
        ];
    }
}
