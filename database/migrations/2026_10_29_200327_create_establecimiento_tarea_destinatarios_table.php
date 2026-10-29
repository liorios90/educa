<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establecimiento_tarea_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_tarea_id');
            $table->unsignedBigInteger('alumno_id');
            $table->decimal('nota', 8, 2)->nullable();
            $table->string('observacion', 500)->nullable();
            $table->timestamp('calificada_at')->nullable();
            $table->timestamps();

            $table->foreign('establecimiento_tarea_id', 'tarea_dest_tarea_fk')
                ->references('id')
                ->on('establecimiento_tareas')
                ->cascadeOnDelete();
            $table->foreign('alumno_id', 'tarea_dest_alumno_fk')
                ->references('id')
                ->on('alumnos')
                ->cascadeOnDelete();
            $table->unique(['establecimiento_tarea_id', 'alumno_id'], 'tarea_destinatario_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimiento_tarea_destinatarios');
    }
};
