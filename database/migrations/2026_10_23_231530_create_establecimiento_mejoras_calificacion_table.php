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
        Schema::create('establecimiento_mejoras_calificacion', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('establecimiento_cierre_actividad_calificacion_id');
            $table->string('tipo_mejora', 20);
            $table->decimal('nota_inicial', 5, 2);
            $table->decimal('nota_refuerzo', 5, 2)->nullable();
            $table->decimal('nota_adicional', 5, 2);
            $table->decimal('nota_final', 5, 2);
            $table->date('fecha_notificacion');
            $table->date('fecha_solicitud');
            $table->date('fecha_evaluacion');
            $table->string('referencia_solicitud')->nullable();
            $table->string('normativa_aplicada', 10);
            $table->unsignedBigInteger('registrado_por');
            $table->timestamps();

            $table->unique('establecimiento_cierre_actividad_calificacion_id', 'est_mej_calificacion_unq');
            $table->foreign('establecimiento_cierre_actividad_calificacion_id', 'est_mej_calificacion_fk')
                ->references('id')
                ->on('establecimiento_cierre_actividad_calificaciones')
                ->cascadeOnDelete();
            $table->foreign('registrado_por', 'est_mej_registrado_por_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_mejoras_calificacion');
    }
};
