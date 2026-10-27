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
        Schema::create('establecimiento_comportamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establecimiento_id')->constrained('establecimientos')->cascadeOnDelete();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->unsignedBigInteger('establecimiento_aula_id');
            $table->unsignedBigInteger('establecimiento_periodo_ciclo_id');
            $table->foreign('establecimiento_aula_id', 'est_comportamientos_aula_fk')
                ->references('id')
                ->on('establecimiento_aulas')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_periodo_ciclo_id', 'est_comportamientos_ciclo_fk')
                ->references('id')
                ->on('establecimiento_periodo_ciclos')
                ->cascadeOnDelete();
            $table->string('calificacion', 1);
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->unique(
                ['alumno_id', 'establecimiento_aula_id', 'establecimiento_periodo_ciclo_id'],
                'est_comportamientos_alumno_aula_ciclo_unq',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_comportamientos');
    }
};
