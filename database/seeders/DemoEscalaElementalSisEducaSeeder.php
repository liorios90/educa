<?php

namespace Database\Seeders;

use App\Enums\CalificacionDestreza;
use App\Enums\TipoCalificacion;
use App\Enums\UsoEquivalencia;
use App\Models\Equivalencia;
use App\Models\Establecimiento;
use App\Models\TipoInsumo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoEscalaElementalSisEducaSeeder extends Seeder
{
    private const CODIGO_AMIE = '01AS01061';

    /**
     * Básica Elemental se califica con A+ a E-. La libreta usa A, EP e I.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $establecimiento = Establecimiento::query()
                ->where('codigo_amie', self::CODIGO_AMIE)
                ->firstOrFail();
            $escala = CalificacionDestreza::escalaElemental();
            $codigos = array_column($escala, 'codigo');

            $tipos = TipoInsumo::query()
                ->where('tipo_calificacion', TipoCalificacion::Cualitativa->value)
                ->whereHas('periodo', fn ($query) => $query->where('establecimiento_id', $establecimiento->id))
                ->get()
                ->filter(fn (TipoInsumo $tipo): bool => $this->esBasicaElemental($tipo->nombre));

            foreach ($tipos as $tipo) {
                foreach ($tipo->detalles as $detalle) {
                    $enUso = DB::table('establecimiento_insumo_actividades')
                        ->where('tipo_insumo_detalle_id', $detalle->id)
                        ->exists();

                    if ($enUso) {
                        if (! in_array($detalle->valor, $codigos, true)) {
                            $detalle->update(['activo' => false]);
                        }

                        continue;
                    }

                    $detalle->delete();
                }

                foreach ($escala as $opcion) {
                    $tipo->detalles()->updateOrCreate(
                        ['valor' => $opcion['codigo']],
                        [
                            'nombre' => $opcion['etiqueta'],
                            'descripcion' => $opcion['descripcion'],
                            'porcentaje' => null,
                            'activo' => true,
                        ],
                    );
                }

                $this->equivalencia((int) $tipo->establecimiento_periodo_id, $escala);
            }
        });
    }

    /**
     * @param  list<array{codigo: string, libreta: string, etiqueta: string, descripcion: string}>  $escala
     */
    private function equivalencia(int $periodoId, array $escala): void
    {
        $equivalencia = Equivalencia::query()->updateOrCreate(
            [
                'establecimiento_periodo_id' => $periodoId,
                'nombre' => 'Equivalencia cualitativa de Básica Elemental',
            ],
            [
                'descripcion' => 'Acuerdo MINEDEC-MINEDEC-2026-00082-A, artículo 7. La libreta muestra A, EP o I.',
                'uso' => UsoEquivalencia::Asignatura,
                'activo' => true,
            ],
        );

        foreach ($escala as $opcion) {
            $equivalencia->detalles()->updateOrCreate(
                ['valor' => $opcion['codigo']],
                [
                    'nombre' => $opcion['libreta'],
                    'descripcion' => $opcion['etiqueta'],
                    'minimo' => null,
                    'maximo' => null,
                    'activo' => true,
                ],
            );
        }
    }

    private function esBasicaElemental(string $nombre): bool
    {
        $normalizado = Str::of($nombre)
            ->ascii()
            ->lower()
            ->squish()
            ->toString();

        return $normalizado === 'basica elemental';
    }
}
