<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('equivalencias', 'uso')) {
            Schema::table('equivalencias', function (Blueprint $table): void {
                $table->string('uso', 30)->default('asignatura');
            });
        }

        if (! Schema::hasColumn('equivalencias_detalles', 'exige_observacion')) {
            Schema::table('equivalencias_detalles', function (Blueprint $table): void {
                $table->boolean('exige_observacion')->default(false);
            });
        }

        if (! Schema::hasColumn('equivalencias_detalles', 'jerarquia')) {
            Schema::table('equivalencias_detalles', function (Blueprint $table): void {
                $table->unsignedTinyInteger('jerarquia')->nullable();
            });
        }

        foreach (['A' => 5, 'B' => 4, 'C' => 3, 'D' => 2, 'E' => 1] as $codigo => $jerarquia) {
            DB::table('equivalencias_detalles')
                ->whereNull('jerarquia')
                ->where('valor', $codigo)
                ->whereIn('equivalencia_id', function ($query): void {
                    $query->select('id')->from('equivalencias')->where('uso', 'comportamiento');
                })
                ->update(['jerarquia' => $jerarquia]);
        }

        if (! Schema::hasColumn('establecimiento_comportamientos', 'equivalencia_detalle_id')) {
            Schema::table('establecimiento_comportamientos', function (Blueprint $table): void {
                $table->unsignedBigInteger('equivalencia_detalle_id')->nullable();
                $table->foreign('equivalencia_detalle_id', 'est_comp_detalle_fk')
                    ->references('id')
                    ->on('equivalencias_detalles')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('establecimiento_comportamientos', 'calificacion')) {
            Schema::table('establecimiento_comportamientos', function (Blueprint $table): void {
                $table->dropColumn('calificacion');
            });
        }

        if (! Schema::hasColumn('establecimiento_comportamientos', 'establecimiento_asignatura_id')) {
            Schema::table('establecimiento_comportamientos', function (Blueprint $table): void {
                $table->unsignedBigInteger('establecimiento_asignatura_id')->nullable();
                $table->unsignedBigInteger('criterio_comportamiento_id')->nullable();
                $table->foreign('establecimiento_asignatura_id', 'est_comp_asignatura_fk')
                    ->references('id')
                    ->on('establecimiento_asignaturas')
                    ->cascadeOnDelete();
                $table->foreign('criterio_comportamiento_id', 'est_comp_criterio_fk')
                    ->references('id')
                    ->on('sys_criterios_comportamiento')
                    ->restrictOnDelete();
            });
        }

        $indices = array_column(Schema::getIndexes('establecimiento_comportamientos'), 'name');

        if (
            in_array('est_comportamientos_alumno_aula_ciclo_unq', $indices, true)
            && ! in_array('est_comp_alumno_idx', $indices, true)
            && ! in_array('establecimiento_comportamientos_alumno_id_foreign', $indices, true)
        ) {
            Schema::table('establecimiento_comportamientos', function (Blueprint $table): void {
                $table->index('alumno_id', 'est_comp_alumno_idx');
            });
        }

        $indices = array_column(Schema::getIndexes('establecimiento_comportamientos'), 'name');

        Schema::table('establecimiento_comportamientos', function (Blueprint $table) use ($indices): void {
            if (in_array('est_comportamientos_alumno_aula_ciclo_unq', $indices, true)) {
                $table->dropUnique('est_comportamientos_alumno_aula_ciclo_unq');
            }

            if (! in_array('est_comp_alumno_indicador_unq', $indices, true)) {
                $table->unique(
                    [
                        'alumno_id',
                        'establecimiento_aula_id',
                        'establecimiento_asignatura_id',
                        'establecimiento_periodo_ciclo_id',
                        'criterio_comportamiento_id',
                    ],
                    'est_comp_alumno_indicador_unq',
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('establecimiento_comportamientos', function (Blueprint $table): void {
            $table->dropUnique('est_comp_alumno_indicador_unq');
            $table->unique(
                ['alumno_id', 'establecimiento_aula_id', 'establecimiento_periodo_ciclo_id'],
                'est_comportamientos_alumno_aula_ciclo_unq',
            );
            $table->dropForeign('est_comp_criterio_fk');
            $table->dropForeign('est_comp_asignatura_fk');
            $table->dropColumn(['establecimiento_asignatura_id', 'criterio_comportamiento_id']);
        });

        Schema::table('equivalencias_detalles', function (Blueprint $table): void {
            $table->dropColumn('jerarquia');
        });
    }
};
