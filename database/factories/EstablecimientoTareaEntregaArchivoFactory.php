<?php

namespace Database\Factories;

use App\Models\EstablecimientoTareaEntrega;
use App\Models\EstablecimientoTareaEntregaArchivo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoTareaEntregaArchivo>
 */
class EstablecimientoTareaEntregaArchivoFactory extends Factory
{
    protected $model = EstablecimientoTareaEntregaArchivo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_tarea_entrega_id' => EstablecimientoTareaEntrega::factory(),
            'path' => 'tareas/entregas/trabajo.pdf',
            'nombre_original' => 'trabajo.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
        ];
    }
}
