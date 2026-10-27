<?php

namespace App\Services;

use App\Enums\CalificacionComportamiento;
use App\Enums\UsoEquivalencia;
use App\Models\Equivalencia;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Support\Facades\DB;

class EscalaComportamiento
{
    public function delPeriodo(int $periodoId): ?Equivalencia
    {
        return Equivalencia::query()
            ->where('establecimiento_periodo_id', $periodoId)
            ->where('uso', UsoEquivalencia::Comportamiento)
            ->where('activo', true)
            ->with(['detalles' => fn ($query) => $query->where('activo', true)->orderBy('id')])
            ->first();
    }

    public function crearReglamento(EstablecimientoPeriodo $periodo): Equivalencia
    {
        return DB::transaction(function () use ($periodo): Equivalencia {
            $equivalencia = Equivalencia::query()->create([
                'establecimiento_periodo_id' => $periodo->id,
                'nombre' => 'Comportamiento',
                'descripcion' => 'Escala del Reglamento General a la LOEI. Puedes cambiar las letras, los nombres y qué calificaciones exigen observación.',
                'uso' => UsoEquivalencia::Comportamiento,
                'activo' => true,
            ]);

            foreach (CalificacionComportamiento::cases() as $escala) {
                $equivalencia->detalles()->create([
                    'nombre' => $escala->label(),
                    'descripcion' => $escala->descripcion(),
                    'valor' => $escala->value,
                    'activo' => true,
                    'exige_observacion' => $escala->exigeObservacion(),
                ]);
            }

            return $equivalencia->load(['detalles' => fn ($query) => $query->orderBy('id')]);
        });
    }
}
