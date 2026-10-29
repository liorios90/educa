<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establecimiento_tareas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establecimiento_id')->constrained('establecimientos')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->foreignId('establecimiento_aula_id')->constrained('establecimiento_aulas')->cascadeOnDelete();
            $table->foreignId('establecimiento_asignatura_id')->constrained('establecimiento_asignaturas')->cascadeOnDelete();
            $table->foreignId('establecimiento_periodo_ciclo_id')->constrained('establecimiento_periodo_ciclos')->cascadeOnDelete();
            $table->unsignedTinyInteger('parcial_numero');
            $table->string('nombre', 160);
            $table->text('descripcion');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('contenido_tipo', 20);
            $table->text('contenido_texto')->nullable();
            $table->string('contenido_url', 500)->nullable();
            $table->string('contenido_path')->nullable();
            $table->string('contenido_nombre')->nullable();
            $table->timestamps();

            $table->index([
                'empleado_id',
                'establecimiento_aula_id',
                'establecimiento_asignatura_id',
                'establecimiento_periodo_ciclo_id',
                'parcial_numero',
            ], 'tareas_contexto_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimiento_tareas');
    }
};
