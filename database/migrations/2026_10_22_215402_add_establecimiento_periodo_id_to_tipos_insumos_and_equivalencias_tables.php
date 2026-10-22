<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_insumos', function (Blueprint $table): void {
            $table->unsignedBigInteger('establecimiento_periodo_id')->nullable()->after('id');
        });

        Schema::table('equivalencias', function (Blueprint $table): void {
            $table->unsignedBigInteger('establecimiento_periodo_id')->nullable()->after('id');
        });

        $this->asignarTiposInsumosAlPeriodo();
        $this->asignarEquivalenciasAlPeriodo();

        Schema::table('tipos_insumos', function (Blueprint $table): void {
            $table->foreign('establecimiento_periodo_id', 'tipos_insumos_periodo_fk')
                ->references('id')
                ->on('establecimiento_periodos')
                ->cascadeOnDelete();
        });

        Schema::table('equivalencias', function (Blueprint $table): void {
            $table->foreign('establecimiento_periodo_id', 'equivalencias_periodo_fk')
                ->references('id')
                ->on('establecimiento_periodos')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tipos_insumos', function (Blueprint $table): void {
            $table->dropForeign('tipos_insumos_periodo_fk');
            $table->dropColumn('establecimiento_periodo_id');
        });

        Schema::table('equivalencias', function (Blueprint $table): void {
            $table->dropForeign('equivalencias_periodo_fk');
            $table->dropColumn('establecimiento_periodo_id');
        });
    }

    private function asignarTiposInsumosAlPeriodo(): void
    {
        $periodos = DB::table('establecimiento_periodos')->orderBy('id')->pluck('id');
        $tipos = DB::table('tipos_insumos')->whereNull('establecimiento_periodo_id')->orderBy('id')->get();

        if ($tipos->isEmpty()) {
            return;
        }

        if ($periodos->isEmpty()) {
            DB::table('tipos_insumos')->whereNull('establecimiento_periodo_id')->delete();

            return;
        }

        foreach ($tipos as $tipo) {
            $detalles = DB::table('tipos_insumos_detalles')
                ->where('tipo_insumo_id', $tipo->id)
                ->orderBy('id')
                ->get();
            $tipoPorPeriodo = [];
            $detallePorPeriodo = [];

            foreach ($periodos as $periodoId) {
                $nuevoTipoId = DB::table('tipos_insumos')->insertGetId([
                    'establecimiento_periodo_id' => $periodoId,
                    'nombre' => $tipo->nombre,
                    'descripcion' => $tipo->descripcion,
                    'tipo_calificacion' => $tipo->tipo_calificacion,
                    'activo' => $tipo->activo,
                ]);
                $tipoPorPeriodo[$periodoId] = $nuevoTipoId;

                foreach ($detalles as $detalle) {
                    $detallePorPeriodo[$detalle->id][$periodoId] = DB::table('tipos_insumos_detalles')->insertGetId([
                        'tipo_insumo_id' => $nuevoTipoId,
                        'nombre' => $detalle->nombre,
                        'descripcion' => $detalle->descripcion,
                        'valor' => $detalle->valor,
                        'porcentaje' => $detalle->porcentaje,
                        'activo' => $detalle->activo,
                    ]);
                }
            }

            $this->remapearAsignacionesTipoInsumo((int) $tipo->id, $tipoPorPeriodo);
            $this->remapearActividadesTipoInsumo($detallePorPeriodo);
            DB::table('tipos_insumos')->where('id', $tipo->id)->delete();
        }
    }

    private function asignarEquivalenciasAlPeriodo(): void
    {
        $periodos = DB::table('establecimiento_periodos')->orderBy('id')->pluck('id');
        $equivalencias = DB::table('equivalencias')->whereNull('establecimiento_periodo_id')->orderBy('id')->get();

        if ($equivalencias->isEmpty()) {
            return;
        }

        if ($periodos->isEmpty()) {
            DB::table('equivalencias')->whereNull('establecimiento_periodo_id')->delete();

            return;
        }

        foreach ($equivalencias as $equivalencia) {
            $detalles = DB::table('equivalencias_detalles')
                ->where('equivalencia_id', $equivalencia->id)
                ->orderBy('id')
                ->get();
            $equivalenciaPorPeriodo = [];

            foreach ($periodos as $periodoId) {
                $nuevaId = DB::table('equivalencias')->insertGetId([
                    'establecimiento_periodo_id' => $periodoId,
                    'nombre' => $equivalencia->nombre,
                    'descripcion' => $equivalencia->descripcion,
                    'activo' => $equivalencia->activo,
                ]);
                $equivalenciaPorPeriodo[$periodoId] = $nuevaId;

                foreach ($detalles as $detalle) {
                    DB::table('equivalencias_detalles')->insert([
                        'equivalencia_id' => $nuevaId,
                        'nombre' => $detalle->nombre,
                        'descripcion' => $detalle->descripcion,
                        'valor' => $detalle->valor,
                        'minimo' => $detalle->minimo,
                        'maximo' => $detalle->maximo,
                        'activo' => $detalle->activo,
                    ]);
                }
            }

            $this->remapearAsignacionesEquivalencia((int) $equivalencia->id, $equivalenciaPorPeriodo);
            DB::table('equivalencias')->where('id', $equivalencia->id)->delete();
        }
    }

    /**
     * @param  array<int, int>  $tipoPorPeriodo
     */
    private function remapearAsignacionesTipoInsumo(int $tipoId, array $tipoPorPeriodo): void
    {
        $asignaciones = DB::table('establecimiento_aula_asignatura_tipo_insumos')
            ->where('tipo_insumo_id', $tipoId)
            ->get();

        foreach ($asignaciones as $asignacion) {
            $periodoId = DB::table('establecimiento_aulas')
                ->where('id', $asignacion->establecimiento_aula_id)
                ->value('establecimiento_periodo_id');

            if (! is_numeric($periodoId) || ! isset($tipoPorPeriodo[(int) $periodoId])) {
                DB::table('establecimiento_aula_asignatura_tipo_insumos')->where('id', $asignacion->id)->delete();

                continue;
            }

            DB::table('establecimiento_aula_asignatura_tipo_insumos')
                ->where('id', $asignacion->id)
                ->update(['tipo_insumo_id' => $tipoPorPeriodo[(int) $periodoId]]);
        }
    }

    /**
     * @param  array<int, array<int, int>>  $detallePorPeriodo
     */
    private function remapearActividadesTipoInsumo(array $detallePorPeriodo): void
    {
        if ($detallePorPeriodo === [] || ! Schema::hasTable('establecimiento_insumo_actividades')) {
            return;
        }

        foreach ($detallePorPeriodo as $detalleId => $idsPorPeriodo) {
            $actividades = DB::table('establecimiento_insumo_actividades')
                ->where('tipo_insumo_detalle_id', $detalleId)
                ->get();

            foreach ($actividades as $actividad) {
                $periodoId = DB::table('establecimiento_aulas')
                    ->where('id', $actividad->establecimiento_aula_id)
                    ->value('establecimiento_periodo_id');

                if (! is_numeric($periodoId) || ! isset($idsPorPeriodo[(int) $periodoId])) {
                    DB::table('establecimiento_insumo_actividades')->where('id', $actividad->id)->delete();

                    continue;
                }

                DB::table('establecimiento_insumo_actividades')
                    ->where('id', $actividad->id)
                    ->update(['tipo_insumo_detalle_id' => $idsPorPeriodo[(int) $periodoId]]);
            }
        }
    }

    /**
     * @param  array<int, int>  $equivalenciaPorPeriodo
     */
    private function remapearAsignacionesEquivalencia(int $equivalenciaId, array $equivalenciaPorPeriodo): void
    {
        if (! Schema::hasTable('establecimiento_aula_asignatura_equivalencias')) {
            return;
        }

        $asignaciones = DB::table('establecimiento_aula_asignatura_equivalencias')
            ->where('equivalencia_id', $equivalenciaId)
            ->get();

        foreach ($asignaciones as $asignacion) {
            $periodoId = DB::table('establecimiento_aulas')
                ->where('id', $asignacion->establecimiento_aula_id)
                ->value('establecimiento_periodo_id');

            if (! is_numeric($periodoId) || ! isset($equivalenciaPorPeriodo[(int) $periodoId])) {
                DB::table('establecimiento_aula_asignatura_equivalencias')->where('id', $asignacion->id)->delete();

                continue;
            }

            DB::table('establecimiento_aula_asignatura_equivalencias')
                ->where('id', $asignacion->id)
                ->update(['equivalencia_id' => $equivalenciaPorPeriodo[(int) $periodoId]]);
        }
    }
};
