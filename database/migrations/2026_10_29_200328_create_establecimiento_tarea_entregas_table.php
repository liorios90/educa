<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establecimiento_tarea_entregas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_tarea_id');
            $table->unsignedBigInteger('alumno_id');
            $table->text('mensaje')->nullable();
            $table->timestamps();

            $table->foreign('establecimiento_tarea_id', 'tarea_entrega_tarea_fk')
                ->references('id')
                ->on('establecimiento_tareas')
                ->cascadeOnDelete();
            $table->foreign('alumno_id', 'tarea_entrega_alumno_fk')
                ->references('id')
                ->on('alumnos')
                ->cascadeOnDelete();
            $table->unique(['establecimiento_tarea_id', 'alumno_id'], 'tarea_entrega_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimiento_tarea_entregas');
    }
};
