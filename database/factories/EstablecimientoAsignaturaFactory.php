<?php

namespace Database\Factories;

use App\Enums\TipoCalificacion;
use App\Models\Establecimiento;
use App\Models\EstablecimientoAsignatura;
use App\Models\EstablecimientoModalidad;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\Sys_Asignatura;
use App\Models\Sys_Grado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstablecimientoAsignatura>
 */
class EstablecimientoAsignaturaFactory extends Factory
{
    protected $model = EstablecimientoAsignatura::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'establecimiento_id' => Establecimiento::factory(),
            'grado_id' => Sys_Grado::factory(),
            'asignatura_id' => Sys_Asignatura::factory(),
            'aparece_en_libreta' => true,
            'horas_semanales' => 5,
            'tipo_calificacion' => TipoCalificacion::Calificacion,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (EstablecimientoAsignatura $malla): void {
            if ($malla->establecimiento_modalidad_jornada_id !== null) {
                return;
            }

            $oferta = $malla->establecimiento_id
                ? EstablecimientoModalidadJornada::factory()->create([
                    'establecimiento_modalidad_id' => EstablecimientoModalidad::factory()->create([
                        'establecimiento_id' => $malla->establecimiento_id,
                    ]),
                ])
                : EstablecimientoModalidadJornada::factory()->create();

            $malla->establecimiento_modalidad_jornada_id = $oferta->id;
            $malla->establecimiento_id = $oferta->establecimientoModalidad->establecimiento_id;
        });
    }
}
