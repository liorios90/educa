<?php

namespace Database\Factories;

use App\Models\Notificacion;
use App\Models\NotificacionDestinatario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificacionDestinatario>
 */
class NotificacionDestinatarioFactory extends Factory
{
    protected $model = NotificacionDestinatario::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notificacion_id' => Notificacion::factory(),
            'user_id' => User::factory(),
            'leida_at' => null,
        ];
    }
}
