<?php

namespace Database\Factories;

use App\Models\Establecimiento;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notificacion>
 */
class NotificacionFactory extends Factory
{
    protected $model = Notificacion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_id' => Establecimiento::factory(),
            'remitente_id' => User::factory(),
            'asunto' => fake()->sentence(4),
            'cuerpo' => fake()->paragraph(),
        ];
    }
}
