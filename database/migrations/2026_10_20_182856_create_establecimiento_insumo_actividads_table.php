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
        Schema::create('establecimiento_insumo_actividades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_aula_id');
            $table->unsignedBigInteger('establecimiento_asignatura_id');
            $table->unsignedBigInteger('establecimiento_periodo_ciclo_id');
            $table->unsignedTinyInteger('parcial_numero');
            $table->unsignedBigInteger('tipo_insumo_detalle_id');
            $table->unsignedBigInteger('empleado_id');
            $table->string('nombre');
            $table->timestamps();

            $table->index(
                ['establecimiento_aula_id', 'establecimiento_asignatura_id', 'establecimiento_periodo_ciclo_id', 'parcial_numero'],
                'est_insumo_act_contexto_idx',
            );
            $table->foreign('establecimiento_aula_id', 'est_insumo_act_aula_fk')
                ->references('id')
                ->on('establecimiento_aulas')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_asignatura_id', 'est_insumo_act_asig_fk')
                ->references('id')
                ->on('establecimiento_asignaturas')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_periodo_ciclo_id', 'est_insumo_act_ciclo_fk')
                ->references('id')
                ->on('establecimiento_periodo_ciclos')
                ->cascadeOnDelete();
            $table->foreign('tipo_insumo_detalle_id', 'est_insumo_act_detalle_fk')
                ->references('id')
                ->on('tipos_insumos_detalles')
                ->cascadeOnDelete();
            $table->foreign('empleado_id', 'est_insumo_act_empleado_fk')
                ->references('id')
                ->on('empleados')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_insumo_actividades');
    }
};
