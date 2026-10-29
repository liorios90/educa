<?php

namespace Database\Factories;

use App\Enums\TipoContenidoTarea;
use App\Models\Empleado;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\EstablecimientoTarea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoTarea>
 */
class EstablecimientoTareaFactory extends Factory
{
    protected $model = EstablecimientoTarea::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_id' => Establecimiento::factory(),
            'empleado_id' => Empleado::factory(),
            'establecimiento_aula_id' => EstablecimientoAula::factory(),
            'establecimiento_asignatura_id' => EstablecimientoAsignatura::factory(),
            'establecimiento_periodo_ciclo_id' => EstablecimientoPeriodoCiclo::factory(),
            'parcial_numero' => 1,
            'nombre' => 'Ensayo de la semana',
            'descripcion' => '<p>Lee el capítulo y responde.</p>',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDays(7)->toDateString(),
            'contenido_tipo' => TipoContenidoTarea::Texto,
            'contenido_texto' => 'Resuelve los ejercicios 1 a 5.',
            'contenido_url' => null,
            'contenido_path' => null,
            'contenido_nombre' => null,
        ];
    }
}
