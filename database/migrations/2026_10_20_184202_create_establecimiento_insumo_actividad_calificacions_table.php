<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('establecimiento_insumo_actividad_calificaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_insumo_actividad_id');
            $table->unsignedBigInteger('alumno_id');
            $table->string('nota', 50);
            $table->timestamps();

            $table->unique(
                ['establecimiento_insumo_actividad_id', 'alumno_id'],
                'est_insumo_act_cal_unica',
            );
            $table->foreign('establecimiento_insumo_actividad_id', 'est_insumo_act_cal_act_fk')
                ->references('id')
                ->on('establecimiento_insumo_actividades')
                ->cascadeOnDelete();
            $table->foreign('alumno_id', 'est_insumo_act_cal_alumno_fk')
                ->references('id')
                ->on('alumnos')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_insumo_actividad_calificaciones');
    }
};
