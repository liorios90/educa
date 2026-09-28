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
        Schema::create('establecimiento_asignaturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_id');
            $table->unsignedBigInteger('establecimiento_modalidad_jornada_id');
            $table->unsignedBigInteger('grado_id');
            $table->unsignedBigInteger('asignatura_id');
            $table->boolean('aparece_en_libreta')->default(true);
            $table->unsignedInteger('horas_semanales')->nullable();
            $table->string('tipo_calificacion', 20);
            $table->timestamps();

            $table->foreign('establecimiento_id', 'est_asig_est_fk')
                ->references('id')
                ->on('establecimientos')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_modalidad_jornada_id', 'est_asig_oferta_fk')
                ->references('id')
                ->on('establecimiento_modalidad_jornadas')
                ->cascadeOnDelete();
            $table->foreign('grado_id', 'est_asig_grado_fk')
                ->references('id')
                ->on('sys_grados')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->foreign('asignatura_id', 'est_asig_asig_fk')
                ->references('id')
                ->on('sys_asignaturas')
                ->restrictOnUpdate()
                ->restrictOnDelete();

            $table->unique(
                ['establecimiento_modalidad_jornada_id', 'grado_id', 'asignatura_id'],
                'est_asig_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_asignaturas');
    }
};
