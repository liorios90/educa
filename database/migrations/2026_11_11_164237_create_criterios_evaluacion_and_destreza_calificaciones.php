<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_criterios_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignatura_id')->constrained('sys_asignaturas')->cascadeOnDelete();
            $table->foreignId('grado_id')->constrained('sys_grados')->cascadeOnDelete();
            $table->string('codigo', 32);
            $table->string('descripcion', 500);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['asignatura_id', 'grado_id', 'codigo'], 'sys_criterios_asig_grado_codigo_unq');
        });

        Schema::table('sys_destrezas', function (Blueprint $table) {
            $table->foreignId('criterio_evaluacion_id')
                ->nullable()
                ->after('grado_id')
                ->constrained('sys_criterios_evaluacion')
                ->nullOnDelete();
            $table->boolean('imprescindible')->default(true)->after('descripcion');
        });

        Schema::create('establecimiento_destreza_calificaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_id');
            $table->unsignedBigInteger('alumno_id');
            $table->unsignedBigInteger('establecimiento_aula_id');
            $table->unsignedBigInteger('establecimiento_asignatura_id');
            $table->unsignedBigInteger('establecimiento_periodo_ciclo_id');
            $table->unsignedBigInteger('destreza_id');
            $table->string('calificacion', 2);
            $table->string('observacion', 500)->nullable();
            $table->timestamps();

            $table->foreign('establecimiento_id', 'est_dest_calif_est_fk')
                ->references('id')
                ->on('establecimientos')
                ->cascadeOnDelete();
            $table->foreign('alumno_id', 'est_dest_calif_alumno_fk')
                ->references('id')
                ->on('alumnos')
                ->cascadeOnDelete();
            $table->foreign('destreza_id', 'est_dest_calif_destreza_fk')
                ->references('id')
                ->on('sys_destrezas')
                ->restrictOnDelete();
            $table->foreign('establecimiento_aula_id', 'est_dest_calif_aula_fk')
                ->references('id')
                ->on('establecimiento_aulas')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_asignatura_id', 'est_dest_calif_asignatura_fk')
                ->references('id')
                ->on('establecimiento_asignaturas')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_periodo_ciclo_id', 'est_dest_calif_ciclo_fk')
                ->references('id')
                ->on('establecimiento_periodo_ciclos')
                ->cascadeOnDelete();
            $table->unique(
                [
                    'alumno_id',
                    'establecimiento_aula_id',
                    'establecimiento_asignatura_id',
                    'establecimiento_periodo_ciclo_id',
                    'destreza_id',
                ],
                'est_dest_calif_unq',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimiento_destreza_calificaciones');

        Schema::table('sys_destrezas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('criterio_evaluacion_id');
            $table->dropColumn('imprescindible');
        });

        Schema::dropIfExists('sys_criterios_evaluacion');
    }
};
