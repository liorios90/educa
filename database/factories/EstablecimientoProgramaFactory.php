<?php

namespace Database\Factories;

use App\Enums\TipoPrograma;
use App\Models\Establecimiento;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoPrograma;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoPrograma>
 */
class EstablecimientoProgramaFactory extends Factory
{
    protected $model = EstablecimientoPrograma::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_id' => Establecimiento::factory(),
            'establecimiento_modalidad_jornada_id' => EstablecimientoModalidadJornada::factory(),
            'establecimiento_periodo_id' => EstablecimientoPeriodo::factory(),
            'tipo' => TipoPrograma::BachilleratoInternacional,
            'nombre' => 'Bachillerato Internacional',
        ];
    }
}
