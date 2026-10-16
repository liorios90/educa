<?php

namespace Database\Factories;

use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoGrupo;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoGrupo>
 */
class EstablecimientoGrupoFactory extends Factory
{
    protected $model = EstablecimientoGrupo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_id' => Establecimiento::factory(),
            'establecimiento_periodo_id' => EstablecimientoPeriodo::factory(),
            'establecimiento_asignatura_id' => EstablecimientoAsignatura::factory(),
            'empleado_id' => null,
            'nombre' => 'Grupo 1',
        ];
    }
}
