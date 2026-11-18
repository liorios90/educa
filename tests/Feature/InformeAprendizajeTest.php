<?php

use App\Enums\TipoCalificacion;
use App\Enums\UsoEquivalencia;
use App\Models\Equivalencia;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\EstablecimientoInsumoActividadCalificacion;
use App\Models\TipoInsumo;

it('muestra en la libreta la equivalencia A, EP o I de la letra calificada', function () {
    $escenario = escenarioLibreta(['asignatura' => 'Ciencias Elemental']);
    $escenario['malla']->update([
        'tipo_calificacion' => TipoCalificacion::Cualitativa,
        'califica_por_destrezas' => false,
    ]);
    $tipo = TipoInsumo::query()->create([
        'establecimiento_periodo_id' => $escenario['periodo']->id,
        'nombre' => 'Basica Elemental',
        'tipo_calificacion' => TipoCalificacion::Cualitativa,
        'activo' => true,
    ]);
    $detalle = $tipo->detalles()->create([
        'nombre' => 'Destreza o aprendizaje en proceso de desarrollo',
        'descripcion' => 'La aplica en situaciones conocidas y colabora con el grupo.',
        'valor' => 'B+',
        'activo' => true,
    ]);
    $equivalencia = Equivalencia::query()->create([
        'establecimiento_periodo_id' => $escenario['periodo']->id,
        'nombre' => 'Equivalencia cualitativa de Básica Elemental',
        'uso' => UsoEquivalencia::Asignatura,
        'activo' => true,
    ]);
    $equivalencia->detalles()->create([
        'nombre' => 'EP',
        'descripcion' => 'Destreza o aprendizaje en proceso de desarrollo',
        'valor' => 'B+',
        'activo' => true,
    ]);
    $alumno = alumnoDelEstablecimiento($escenario['establecimiento'], 'Herrera');
    matricularAlumnoLibreta($escenario['aula'], $alumno);
    $actividad = EstablecimientoInsumoActividad::query()->create([
        'establecimiento_aula_id' => $escenario['aula']->id,
        'establecimiento_asignatura_id' => $escenario['malla']->id,
        'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
        'parcial_numero' => 1,
        'tipo_insumo_detalle_id' => $detalle->id,
        'empleado_id' => $escenario['empleado']->id,
        'nombre' => 'Observación de ciencias',
    ]);
    EstablecimientoInsumoActividadCalificacion::query()->create([
        'establecimiento_insumo_actividad_id' => $actividad->id,
        'alumno_id' => $alumno->id,
        'nota' => 'B+',
    ]);

    $this->actingAs($escenario['secretaria'])
        ->withSession(sesionLibreta($escenario))
        ->get(route('secretaria.informes-aprendizaje.index', [
            'establecimiento_aula_id' => $escenario['aula']->id,
            'establecimiento_periodo_ciclo_id' => $escenario['ciclo']->id,
            'tipo' => 'ciclo',
        ]))
        ->assertOk()
        ->assertSeeInOrder(['Ciencias Elemental', 'B+', 'EP']);
});
