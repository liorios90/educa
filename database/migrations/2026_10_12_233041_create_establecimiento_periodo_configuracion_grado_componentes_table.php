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
        Schema::create('establecimiento_periodo_configuracion_grado_componentes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('configuracion_grado_id');
            $table->unsignedTinyInteger('ciclo_orden');
            $table->decimal('porcentaje_insumos', 5, 2);
            $table->decimal('porcentaje_examen', 5, 2);
            $table->decimal('porcentaje_proyecto', 5, 2);
            $table->timestamps();

            $table->foreign('configuracion_grado_id', 'epcgc_config_fk')
                ->references('id')->on('establecimiento_periodo_configuraciones_grado')->cascadeOnDelete();
            $table->unique(['configuracion_grado_id', 'ciclo_orden'], 'epcgc_config_ciclo_uk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_periodo_configuracion_grado_componentes');
    }
};
