<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('establecimiento_periodos', function (Blueprint $table) {
            $table->decimal('porcentaje_examen_final', 5, 2)->default(0);
            $table->decimal('porcentaje_proyecto_final', 5, 2)->default(0);
        });

        Schema::table('establecimiento_periodo_ciclos', function (Blueprint $table) {
            $table->decimal('porcentaje_examen', 5, 2)->default(0);
            $table->decimal('porcentaje_proyecto', 5, 2)->default(0);
        });

        foreach (DB::table('establecimiento_periodo_ciclos')->orderBy('id')->get() as $ciclo) {
            $cierre = (string) $ciclo->porcentaje_cierre;
            $examen = $ciclo->tipo_cierre === 'examen' ? $cierre : '0.00';
            $proyecto = $ciclo->tipo_cierre === 'proyecto' ? $cierre : '0.00';

            DB::table('establecimiento_periodo_ciclos')
                ->where('id', $ciclo->id)
                ->update([
                    'porcentaje_examen' => $examen,
                    'porcentaje_proyecto' => $proyecto,
                ]);
        }

        Schema::table('establecimiento_periodo_ciclos', function (Blueprint $table) {
            $table->dropColumn(['tipo_cierre', 'porcentaje_cierre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_periodo_ciclos', function (Blueprint $table) {
            $table->string('tipo_cierre')->default('ninguno');
            $table->decimal('porcentaje_cierre', 5, 2)->default(0);
        });

        foreach (DB::table('establecimiento_periodo_ciclos')->orderBy('id')->get() as $ciclo) {
            $examen = (float) $ciclo->porcentaje_examen;
            $proyecto = (float) $ciclo->porcentaje_proyecto;
            $tipo = $examen > 0.009 ? 'examen' : ($proyecto > 0.009 ? 'proyecto' : 'ninguno');
            $cierre = $tipo === 'examen' ? $ciclo->porcentaje_examen : ($tipo === 'proyecto' ? $ciclo->porcentaje_proyecto : '0.00');

            DB::table('establecimiento_periodo_ciclos')
                ->where('id', $ciclo->id)
                ->update([
                    'tipo_cierre' => $tipo,
                    'porcentaje_cierre' => $cierre,
                ]);
        }

        Schema::table('establecimiento_periodo_ciclos', function (Blueprint $table) {
            $table->dropColumn(['porcentaje_examen', 'porcentaje_proyecto']);
        });

        Schema::table('establecimiento_periodos', function (Blueprint $table) {
            $table->dropColumn(['porcentaje_examen_final', 'porcentaje_proyecto_final']);
        });
    }
};
