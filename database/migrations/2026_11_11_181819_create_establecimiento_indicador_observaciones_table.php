<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establecimiento_indicador_observaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_id');
            $table->unsignedBigInteger('alumno_id');
            $table->unsignedBigInteger('establecimiento_aula_id');
            $table->unsignedBigInteger('establecimiento_asignatura_id');
            $table->unsignedBigInteger('establecimiento_periodo_ciclo_id');
            $table->unsignedBigInteger('indicador_evaluacion_id');
            $table->timestamps();

            $table->foreign('establecimiento_id', 'est_ind_obs_est_fk')
                ->references('id')
                ->on('establecimientos')
                ->cascadeOnDelete();
            $table->foreign('alumno_id', 'est_ind_obs_alumno_fk')
                ->references('id')
                ->on('alumnos')
                ->cascadeOnDelete();
            $table->foreign('indicador_evaluacion_id', 'est_ind_obs_indicador_fk')
                ->references('id')
                ->on('sys_indicadores_evaluacion')
                ->restrictOnDelete();
            $table->foreign('establecimiento_aula_id', 'est_ind_obs_aula_fk')
                ->references('id')
                ->on('establecimiento_aulas')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_asignatura_id', 'est_ind_obs_asignatura_fk')
                ->references('id')
                ->on('establecimiento_asignaturas')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_periodo_ciclo_id', 'est_ind_obs_ciclo_fk')
                ->references('id')
                ->on('establecimiento_periodo_ciclos')
                ->cascadeOnDelete();
            $table->unique(
                [
                    'alumno_id',
                    'establecimiento_aula_id',
                    'establecimiento_asignatura_id',
                    'establecimiento_periodo_ciclo_id',
                    'indicador_evaluacion_id',
                ],
                'est_ind_obs_unq',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimiento_indicador_observaciones');
    }
};
