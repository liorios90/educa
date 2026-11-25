<?php

namespace Database\Factories;

use App\Models\Notificacion;
use App\Models\NotificacionArchivo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificacionArchivo>
 */
class NotificacionArchivoFactory extends Factory
{
    protected $model = NotificacionArchivo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notificacion_id' => Notificacion::factory(),
            'path' => 'notificaciones/adjuntos/'.fake()->uuid().'.pdf',
            'nombre_original' => 'circular.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
        ];
    }
}
