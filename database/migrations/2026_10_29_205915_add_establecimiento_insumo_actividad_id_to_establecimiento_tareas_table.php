<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('establecimiento_tareas', function (Blueprint $table) {
            $table->unsignedBigInteger('establecimiento_insumo_actividad_id')->nullable()->after('contenido_nombre');
            $table->foreign('establecimiento_insumo_actividad_id', 'tareas_insumo_act_fk')
                ->references('id')
                ->on('establecimiento_insumo_actividades')
                ->nullOnDelete();
            $table->unique('establecimiento_insumo_actividad_id', 'tareas_insumo_act_unq');
        });
    }

    public function down(): void
    {
        Schema::table('establecimiento_tareas', function (Blueprint $table) {
            $table->dropForeign('tareas_insumo_act_fk');
            $table->dropUnique('tareas_insumo_act_unq');
            $table->dropColumn('establecimiento_insumo_actividad_id');
        });
    }
};
