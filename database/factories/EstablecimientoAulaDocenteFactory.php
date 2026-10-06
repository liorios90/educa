<?php

namespace Database\Factories;

use App\Models\Empleado;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoAulaDocente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoAulaDocente>
 */
class EstablecimientoAulaDocenteFactory extends Factory
{
    protected $model = EstablecimientoAulaDocente::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_aula_id' => EstablecimientoAula::factory(),
            'establecimiento_asignatura_id' => EstablecimientoAsignatura::factory(),
            'empleado_id' => Empleado::factory(),
        ];
    }
}
