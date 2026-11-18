<?php

namespace App\Services;

use App\Models\EstablecimientoCalificacion;
use App\Models\EstablecimientoCierreActividad;
use App\Models\EstablecimientoComportamiento;
use App\Models\EstablecimientoDestrezaCalificacion;
use App\Models\EstablecimientoIndicadorCalificacion;
use App\Models\EstablecimientoInsumoActividad;
use App\Models\EstablecimientoPeriodo;
use App\Models\EstablecimientoPeriodoCiclo;
use App\Models\EstablecimientoPeriodoCicloParcial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RevertirAperturaPeriodo
{
    public const MENSAJE_CON_CALIFICACIONES = 'No se puede revertir la apertura porque ya hay calificaciones ingresadas.';

    public const MENSAJE_SIN_APERTURA = 'Este periodo no está aperturado.';

    public function handle(EstablecimientoPeriodo $periodo): void
    {
        DB::transaction(function () use ($periodo): void {
            $periodo = EstablecimientoPeriodo::query()
                ->whereKey($periodo->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $periodo->estaAbierto()) {
                throw ValidationException::withMessages([
                    'periodo' => self::MENSAJE_SIN_APERTURA,
                ]);
            }

            if ($this->tieneCalificaciones($periodo)) {
                throw ValidationException::withMessages([
                    'periodo' => self::MENSAJE_CON_CALIFICACIONES,
                ]);
            }

            EstablecimientoPeriodoCicloParcial::query()
                ->whereIn('establecimiento_periodo_ciclo_id', $this->ciclosDelPeriodo($periodo))
                ->delete();

            $periodo->update([
                'abierto_en' => null,
            ]);
        });
    }

    public function tieneCalificaciones(EstablecimientoPeriodo $periodo): bool
    {
        if (EstablecimientoCalificacion::query()
            ->where('establecimiento_periodo_id', $periodo->id)
            ->exists()) {
            return true;
        }

        if (EstablecimientoInsumoActividad::query()
            ->whereIn('establecimiento_periodo_ciclo_id', $this->ciclosDelPeriodo($periodo))
            ->whereHas('calificaciones')
            ->exists()) {
            return true;
        }

        if (EstablecimientoCierreActividad::query()
            ->whereIn('establecimiento_periodo_ciclo_id', $this->ciclosDelPeriodo($periodo))
            ->whereHas('calificaciones')
            ->exists()) {
            return true;
        }

        if (EstablecimientoDestrezaCalificacion::query()
            ->whereIn('establecimiento_periodo_ciclo_id', $this->ciclosDelPeriodo($periodo))
            ->exists()) {
            return true;
        }

        if (EstablecimientoIndicadorCalificacion::query()
            ->whereIn('establecimiento_periodo_ciclo_id', $this->ciclosDelPeriodo($periodo))
            ->exists()) {
            return true;
        }

        return EstablecimientoComportamiento::query()
            ->whereIn('establecimiento_periodo_ciclo_id', $this->ciclosDelPeriodo($periodo))
            ->exists();
    }

    /**
     * @return Builder<EstablecimientoPeriodoCiclo>
     */
    private function ciclosDelPeriodo(EstablecimientoPeriodo $periodo): Builder
    {
        return EstablecimientoPeriodoCiclo::query()
            ->select('id')
            ->where('establecimiento_periodo_id', $periodo->id);
    }
}
