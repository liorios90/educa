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
        Schema::create('establecimiento_periodo_configuracion_grado_grados', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('configuracion_grado_id');
            $table->unsignedBigInteger('grado_id');
            $table->timestamps();

            $table->foreign('configuracion_grado_id', 'epcgg_config_fk')
                ->references('id')->on('establecimiento_periodo_configuraciones_grado')->cascadeOnDelete();
            $table->foreign('grado_id', 'epcgg_grado_fk')->references('id')->on('sys_grados')->cascadeOnDelete();
            $table->unique(['configuracion_grado_id', 'grado_id'], 'epcgg_config_grado_uk');
        });

        Schema::create('establecimiento_periodo_configuracion_grado_ciclos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('configuracion_grado_id');
            $table->unsignedBigInteger('establecimiento_asignatura_id');
            $table->unsignedTinyInteger('ciclo_orden');
            $table->decimal('porcentaje', 5, 2);
            $table->timestamps();

            $table->foreign('configuracion_grado_id', 'epcgcc_config_fk')
                ->references('id')->on('establecimiento_periodo_configuraciones_grado')->cascadeOnDelete();
            $table->foreign('establecimiento_asignatura_id', 'epcgcc_asig_fk')
                ->references('id')->on('establecimiento_asignaturas')->cascadeOnDelete();
            $table->unique(['configuracion_grado_id', 'establecimiento_asignatura_id', 'ciclo_orden'], 'epcgcc_config_asig_ciclo_uk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_periodo_configuracion_grado_ciclos');
        Schema::dropIfExists('establecimiento_periodo_configuracion_grado_grados');
    }
};
