<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alumno>
 */
class AlumnoFactory extends Factory
{
    protected $model = Alumno::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'persona_id' => Persona::factory(),
            'padre_id' => null,
            'contacto_emergencia' => fake()->numerify('09########'),
            'usuario' => fake()->userName(),
            'id_estructura_form_matricula' => 0,
            'activo' => 1,
        ];
    }
}
