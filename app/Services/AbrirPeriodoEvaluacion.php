<?php

namespace App\Services;

use App\Models\EstablecimientoPeriodo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AbrirPeriodoEvaluacion
{
    public function handle(EstablecimientoPeriodo $periodo): void
    {
        DB::transaction(function () use ($periodo): void {
            $periodo = EstablecimientoPeriodo::query()
                ->whereKey($periodo->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($periodo->estaAbierto()) {
                throw ValidationException::withMessages([
                    'periodo' => 'Este periodo ya fue aperturado. La configuración está bloqueada.',
                ]);
            }

            $periodo->load('ciclos');

            if ($periodo->ciclos->isEmpty() || $periodo->numero_parciales === null || $periodo->numero_parciales < 1) {
                throw ValidationException::withMessages([
                    'periodo' => 'Guarda la configuración del periodo antes de aperturarlo.',
                ]);
            }

            foreach ($periodo->ciclos as $ciclo) {
                for ($numero = 1; $numero <= $periodo->numero_parciales; $numero++) {
                    $ciclo->parciales()->create([
                        'numero' => $numero,
                    ]);
                }
            }

            $periodo->update([
                'abierto_en' => now(),
            ]);
        });
    }
}
