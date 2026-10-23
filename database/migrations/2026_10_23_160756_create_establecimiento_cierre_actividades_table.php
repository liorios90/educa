<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establecimiento_cierre_actividades', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('establecimiento_aula_id');
            $table->unsignedBigInteger('establecimiento_asignatura_id');
            $table->unsignedBigInteger('establecimiento_periodo_ciclo_id');
            $table->string('tipo', 20);
            $table->unsignedBigInteger('empleado_id');
            $table->string('nombre');
            $table->timestamps();

            $table->index(
                ['establecimiento_aula_id', 'establecimiento_asignatura_id', 'establecimiento_periodo_ciclo_id', 'tipo'],
                'est_cierre_act_contexto_idx',
            );
            $table->foreign('establecimiento_aula_id', 'est_cierre_act_aula_fk')
                ->references('id')
                ->on('establecimiento_aulas')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_asignatura_id', 'est_cierre_act_asig_fk')
                ->references('id')
                ->on('establecimiento_asignaturas')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_periodo_ciclo_id', 'est_cierre_act_ciclo_fk')
                ->references('id')
                ->on('establecimiento_periodo_ciclos')
                ->cascadeOnDelete();
            $table->foreign('empleado_id', 'est_cierre_act_empleado_fk')
                ->references('id')
                ->on('empleados')
                ->cascadeOnDelete();
        });

        Schema::create('establecimiento_cierre_actividad_calificaciones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('establecimiento_cierre_actividad_id');
            $table->unsignedBigInteger('alumno_id');
            $table->string('nota', 50);
            $table->timestamps();

            $table->unique(['establecimiento_cierre_actividad_id', 'alumno_id'], 'est_cierre_cal_actividad_alumno_uk');
            $table->foreign('establecimiento_cierre_actividad_id', 'est_cierre_cal_actividad_fk')
                ->references('id')
                ->on('establecimiento_cierre_actividades')
                ->cascadeOnDelete();
            $table->foreign('alumno_id', 'est_cierre_cal_alumno_fk')
                ->references('id')
                ->on('alumnos')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimiento_cierre_actividad_calificaciones');
        Schema::dropIfExists('establecimiento_cierre_actividades');
    }
};
