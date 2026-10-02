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
        Schema::table('establecimiento_periodos', function (Blueprint $table) {
            $table->string('esquema_ciclo')->nullable();
            $table->unsignedTinyInteger('numero_parciales')->nullable();
        });

        Schema::create('establecimiento_periodo_ciclos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_periodo_id');
            $table->unsignedTinyInteger('orden');
            $table->string('nombre');
            $table->decimal('porcentaje', 5, 2);
            $table->string('tipo_cierre');
            $table->decimal('porcentaje_insumos', 5, 2);
            $table->decimal('porcentaje_cierre', 5, 2);
            $table->timestamps();

            $table->unique(['establecimiento_periodo_id', 'orden'], 'est_per_ciclo_orden_uk');
            $table->foreign('establecimiento_periodo_id', 'est_per_ciclo_per_fk')
                ->references('id')
                ->on('establecimiento_periodos')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_periodo_ciclos');

        Schema::table('establecimiento_periodos', function (Blueprint $table) {
            $table->dropColumn(['esquema_ciclo', 'numero_parciales']);
        });
    }
};
