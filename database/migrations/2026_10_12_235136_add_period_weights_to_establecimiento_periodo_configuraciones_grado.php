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
        Schema::table('establecimiento_periodo_configuraciones_grado', function (Blueprint $table): void {
            $table->decimal('porcentaje_examen_final', 5, 2)->nullable();
            $table->decimal('porcentaje_proyecto_final', 5, 2)->nullable();
        });

        Schema::table('establecimiento_periodo_configuracion_grado_componentes', function (Blueprint $table): void {
            $table->decimal('porcentaje_periodo', 5, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_periodo_configuracion_grado_componentes', function (Blueprint $table): void {
            $table->dropColumn('porcentaje_periodo');
        });

        Schema::table('establecimiento_periodo_configuraciones_grado', function (Blueprint $table): void {
            $table->dropColumn(['porcentaje_examen_final', 'porcentaje_proyecto_final']);
        });
    }
};
