<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_subniveles', function (Blueprint $table) {
            $table->boolean('equivale_nota_nacional')->default(false)->after('califica_por_destrezas');
        });

        Schema::create('sys_destrezas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignatura_id')->constrained('sys_asignaturas')->cascadeOnDelete();
            $table->foreignId('grado_id')->constrained('sys_grados')->cascadeOnDelete();
            $table->string('codigo', 20);
            $table->string('descripcion', 500);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['asignatura_id', 'grado_id', 'codigo'], 'sys_destrezas_asignatura_grado_codigo_unq');
        });

        Schema::create('sys_indicadores_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destreza_id')->constrained('sys_destrezas')->cascadeOnDelete();
            $table->string('codigo', 20);
            $table->string('descripcion', 500);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['destreza_id', 'codigo'], 'sys_indicadores_destreza_codigo_unq');
        });

        Schema::create('establecimiento_indicador_calificaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_id');
            $table->unsignedBigInteger('alumno_id');
            $table->unsignedBigInteger('establecimiento_aula_id');
            $table->unsignedBigInteger('establecimiento_asignatura_id');
            $table->unsignedBigInteger('establecimiento_periodo_ciclo_id');
            $table->unsignedBigInteger('indicador_evaluacion_id');
            $table->string('calificacion', 2);
            $table->string('observacion', 500)->nullable();
            $table->timestamps();

            $table->foreign('establecimiento_id', 'est_ind_calif_est_fk')
                ->references('id')
                ->on('establecimientos')
                ->cascadeOnDelete();
            $table->foreign('alumno_id', 'est_ind_calif_alumno_fk')
                ->references('id')
                ->on('alumnos')
                ->cascadeOnDelete();
            $table->foreign('indicador_evaluacion_id', 'est_ind_calif_indicador_fk')
                ->references('id')
                ->on('sys_indicadores_evaluacion')
                ->restrictOnDelete();
            $table->foreign('establecimiento_aula_id', 'est_ind_calif_aula_fk')
                ->references('id')
                ->on('establecimiento_aulas')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_asignatura_id', 'est_ind_calif_asignatura_fk')
                ->references('id')
                ->on('establecimiento_asignaturas')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_periodo_ciclo_id', 'est_ind_calif_ciclo_fk')
                ->references('id')
                ->on('establecimiento_periodo_ciclos')
                ->cascadeOnDelete();
            $table->unique(
                [
                    'alumno_id',
                    'establecimiento_aula_id',
                    'establecimiento_asignatura_id',
                    'establecimiento_periodo_ciclo_id',
                    'indicador_evaluacion_id',
                ],
                'est_ind_calif_unq',
            );
            $table->index(
                ['establecimiento_aula_id', 'establecimiento_asignatura_id', 'establecimiento_periodo_ciclo_id'],
                'est_ind_calif_aula_asig_ciclo_idx',
            );
        });

        $elementalIds = DB::table('sys_subniveles')
            ->where('nombre', 'Básica Elemental')
            ->pluck('id');

        if ($elementalIds->isEmpty()) {
            return;
        }

        DB::table('sys_subniveles')
            ->whereIn('id', $elementalIds)
            ->update([
                'califica_por_destrezas' => true,
                'equivale_nota_nacional' => true,
            ]);

        $gradoIds = DB::table('sys_grados')
            ->whereIn('subnivel_id', $elementalIds)
            ->pluck('id');

        if ($gradoIds->isEmpty()) {
            return;
        }

        DB::table('establecimiento_asignaturas')
            ->whereIn('grado_id', $gradoIds)
            ->where('tipo_calificacion', 'cualitativa')
            ->update(['califica_por_destrezas' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimiento_indicador_calificaciones');
        Schema::dropIfExists('sys_indicadores_evaluacion');
        Schema::dropIfExists('sys_destrezas');

        Schema::table('sys_subniveles', function (Blueprint $table) {
            $table->dropColumn('equivale_nota_nacional');
        });
    }
};
