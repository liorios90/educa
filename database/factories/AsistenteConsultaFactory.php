<?php

namespace Database\Factories;

use App\Models\AsistenteConsulta;
use App\Models\Establecimiento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AsistenteConsulta>
 */
class AsistenteConsultaFactory extends Factory
{
    protected $model = AsistenteConsulta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory();

        return [
            'establecimiento_id' => Establecimiento::factory(),
            'user_id' => $user,
            'usuario' => fake()->name(),
            'pregunta' => fake()->sentence(),
        ];
    }
}
