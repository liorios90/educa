<?php

namespace Database\Factories;

use App\Models\Persona;
use App\Models\Sys_Provincia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Persona>
 */
class PersonaFactory extends Factory
{
    protected $model = Persona::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provincia = Sys_Provincia::factory()->create();

        return [
            'tipo_identificacion_id' => 1,
            'identificacion' => fake()->unique()->numerify('##########'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'genero_id' => 1,
            'fecha_nacimiento' => fake()->date(),
            'ciudad_nacimiento' => fake()->city(),
            'provincia_id' => $provincia->id,
            'parroquia' => fake()->streetName(),
            'direccion' => fake()->streetAddress(),
            'telefono1' => fake()->numerify('02######'),
            'telefono2' => fake()->numerify('09########'),
            'nacionalidad_id' => $provincia->pais_id,
            'usuario' => fake()->userName(),
            'activo' => 1,
        ];
    }
}
