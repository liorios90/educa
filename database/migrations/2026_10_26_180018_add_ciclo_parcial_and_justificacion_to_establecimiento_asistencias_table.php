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
        Schema::table('establecimiento_asistencias', function (Blueprint $table) {
            $table->unsignedBigInteger('establecimiento_periodo_ciclo_id')->nullable()->after('establecimiento_aula_id');
            $table->unsignedTinyInteger('parcial_numero')->nullable()->after('establecimiento_periodo_ciclo_id');
            $table->string('justificacion', 40)->nullable()->after('estado');

            $table->foreign('establecimiento_periodo_ciclo_id', 'est_asistencias_ciclo_fk')
                ->references('id')
                ->on('establecimiento_periodo_ciclos')
                ->nullOnDelete();

            $table->unique(
                ['alumno_id', 'establecimiento_asignatura_id', 'fecha'],
                'est_asistencias_alumno_asignatura_fecha_unq',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_asistencias', function (Blueprint $table) {
            $table->dropForeign('est_asistencias_ciclo_fk');
            $table->dropUnique('est_asistencias_alumno_asignatura_fecha_unq');
            $table->dropColumn([
                'establecimiento_periodo_ciclo_id',
                'parcial_numero',
                'justificacion',
            ]);
        });
    }
};
