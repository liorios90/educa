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
        Schema::create('establecimiento_asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establecimiento_id')->constrained('establecimientos')->cascadeOnDelete();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->unsignedBigInteger('establecimiento_asignatura_id')->nullable();
            $table->unsignedBigInteger('establecimiento_aula_id')->nullable();
            $table->foreign('establecimiento_asignatura_id', 'est_asistencias_asignatura_fk')
                ->references('id')
                ->on('establecimiento_asignaturas')
                ->nullOnDelete();
            $table->foreign('establecimiento_aula_id', 'est_asistencias_aula_fk')
                ->references('id')
                ->on('establecimiento_aulas')
                ->nullOnDelete();
            $table->date('fecha');
            $table->string('estado', 20);
            $table->timestamps();

            $table->index(['establecimiento_id', 'alumno_id', 'fecha'], 'est_asistencias_alumno_fecha_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_asistencias');
    }
};
