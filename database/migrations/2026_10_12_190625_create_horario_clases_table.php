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
        Schema::create('horario_clases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establecimiento_aula_id')
                ->constrained('establecimiento_aulas', 'id', 'horario_aula_fk')
                ->cascadeOnDelete();
            $table->foreignId('establecimiento_asignatura_id')
                ->constrained('establecimiento_asignaturas', 'id', 'horario_malla_fk')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();

            $table->index(['establecimiento_aula_id', 'dia_semana', 'hora_inicio'], 'horario_aula_dia_inicio_idx');
            $table->index(['dia_semana', 'hora_inicio', 'hora_fin'], 'horario_dia_horas_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horario_clases');
    }
};
