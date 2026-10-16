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
        Schema::create('establecimiento_matriculas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_id');
            $table->unsignedBigInteger('establecimiento_periodo_id');
            $table->unsignedBigInteger('establecimiento_aula_id');
            $table->unsignedBigInteger('alumno_id');
            $table->timestamps();

            $table->foreign('establecimiento_id', 'est_mat_est_fk')
                ->references('id')->on('establecimientos')->cascadeOnDelete();
            $table->foreign('establecimiento_periodo_id', 'est_mat_per_fk')
                ->references('id')->on('establecimiento_periodos')->cascadeOnDelete();
            $table->foreign('establecimiento_aula_id', 'est_mat_aula_fk')
                ->references('id')->on('establecimiento_aulas')->cascadeOnDelete();
            $table->foreign('alumno_id', 'est_mat_alu_fk')
                ->references('id')->on('alumnos')->cascadeOnDelete();
            $table->unique(['establecimiento_periodo_id', 'alumno_id'], 'est_mat_periodo_alumno_uk');
        });

        Schema::create('establecimiento_programas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_id');
            $table->unsignedBigInteger('establecimiento_modalidad_jornada_id');
            $table->unsignedBigInteger('establecimiento_periodo_id');
            $table->string('tipo', 40);
            $table->string('nombre', 150);
            $table->timestamps();

            $table->foreign('establecimiento_id', 'est_prog_est_fk')
                ->references('id')->on('establecimientos')->cascadeOnDelete();
            $table->foreign('establecimiento_modalidad_jornada_id', 'est_prog_oferta_fk')
                ->references('id')->on('establecimiento_modalidad_jornadas')->cascadeOnDelete();
            $table->foreign('establecimiento_periodo_id', 'est_prog_per_fk')
                ->references('id')->on('establecimiento_periodos')->cascadeOnDelete();
            $table->unique(
                ['establecimiento_modalidad_jornada_id', 'establecimiento_periodo_id', 'tipo'],
                'est_prog_oferta_periodo_tipo_uk',
            );
        });

        Schema::table('establecimiento_asignaturas', function (Blueprint $table) {
            $table->boolean('es_flotante')->default(false)->after('tipo_asignatura');
            $table->string('escala', 40)->default('nacional')->after('es_flotante');
            $table->unsignedBigInteger('establecimiento_programa_id')->nullable()->after('escala');
            $table->foreign('establecimiento_programa_id', 'est_asig_prog_fk')
                ->references('id')->on('establecimiento_programas')->nullOnDelete();
        });

        Schema::create('establecimiento_programa_alumnos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_programa_id');
            $table->unsignedBigInteger('alumno_id');
            $table->timestamps();

            $table->foreign('establecimiento_programa_id', 'est_prog_alu_prog_fk')
                ->references('id')->on('establecimiento_programas')->cascadeOnDelete();
            $table->foreign('alumno_id', 'est_prog_alu_alu_fk')
                ->references('id')->on('alumnos')->cascadeOnDelete();
            $table->unique(['establecimiento_programa_id', 'alumno_id'], 'est_prog_alu_uk');
        });

        Schema::create('establecimiento_alumno_asignaturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_periodo_id');
            $table->unsignedBigInteger('establecimiento_asignatura_id');
            $table->unsignedBigInteger('alumno_id');
            $table->timestamps();

            $table->foreign('establecimiento_periodo_id', 'est_alu_asig_per_fk')
                ->references('id')->on('establecimiento_periodos')->cascadeOnDelete();
            $table->foreign('establecimiento_asignatura_id', 'est_alu_asig_asig_fk')
                ->references('id')->on('establecimiento_asignaturas')->restrictOnDelete();
            $table->foreign('alumno_id', 'est_alu_asig_alu_fk')
                ->references('id')->on('alumnos')->cascadeOnDelete();
            $table->unique(
                ['establecimiento_periodo_id', 'establecimiento_asignatura_id', 'alumno_id'],
                'est_alu_asig_uk',
            );
        });

        Schema::create('establecimiento_grupos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_id');
            $table->unsignedBigInteger('establecimiento_periodo_id');
            $table->unsignedBigInteger('establecimiento_asignatura_id');
            $table->unsignedBigInteger('empleado_id')->nullable();
            $table->string('nombre', 150);
            $table->timestamps();

            $table->foreign('establecimiento_id', 'est_grp_est_fk')
                ->references('id')->on('establecimientos')->cascadeOnDelete();
            $table->foreign('establecimiento_periodo_id', 'est_grp_per_fk')
                ->references('id')->on('establecimiento_periodos')->cascadeOnDelete();
            $table->foreign('establecimiento_asignatura_id', 'est_grp_asig_fk')
                ->references('id')->on('establecimiento_asignaturas')->restrictOnDelete();
            $table->foreign('empleado_id', 'est_grp_emp_fk')
                ->references('id')->on('empleados')->nullOnDelete();
            $table->unique(
                ['establecimiento_periodo_id', 'establecimiento_asignatura_id', 'nombre'],
                'est_grp_nombre_uk',
            );
        });

        Schema::create('establecimiento_grupo_alumnos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_grupo_id');
            $table->unsignedBigInteger('establecimiento_periodo_id');
            $table->unsignedBigInteger('establecimiento_asignatura_id');
            $table->unsignedBigInteger('alumno_id');
            $table->timestamps();

            $table->foreign('establecimiento_grupo_id', 'est_grp_alu_grp_fk')
                ->references('id')->on('establecimiento_grupos')->cascadeOnDelete();
            $table->foreign('establecimiento_periodo_id', 'est_grp_alu_per_fk')
                ->references('id')->on('establecimiento_periodos')->cascadeOnDelete();
            $table->foreign('establecimiento_asignatura_id', 'est_grp_alu_asig_fk')
                ->references('id')->on('establecimiento_asignaturas')->restrictOnDelete();
            $table->foreign('alumno_id', 'est_grp_alu_alu_fk')
                ->references('id')->on('alumnos')->cascadeOnDelete();
            $table->unique(['establecimiento_grupo_id', 'alumno_id'], 'est_grp_alu_miembro_uk');
            $table->unique(
                ['establecimiento_periodo_id', 'establecimiento_asignatura_id', 'alumno_id'],
                'est_grp_alu_asignatura_uk',
            );
        });

        Schema::create('establecimiento_calificaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_periodo_id');
            $table->unsignedBigInteger('establecimiento_asignatura_id');
            $table->unsignedBigInteger('alumno_id');
            $table->unsignedTinyInteger('ciclo_orden');
            $table->unsignedTinyInteger('parcial_numero')->default(0);
            $table->decimal('nota', 5, 2);
            $table->unsignedBigInteger('establecimiento_grupo_id')->nullable();
            $table->timestamps();

            $table->foreign('establecimiento_periodo_id', 'est_calif_per_fk')
                ->references('id')->on('establecimiento_periodos')->cascadeOnDelete();
            $table->foreign('establecimiento_asignatura_id', 'est_calif_asig_fk')
                ->references('id')->on('establecimiento_asignaturas')->restrictOnDelete();
            $table->foreign('alumno_id', 'est_calif_alu_fk')
                ->references('id')->on('alumnos')->cascadeOnDelete();
            $table->foreign('establecimiento_grupo_id', 'est_calif_grp_fk')
                ->references('id')->on('establecimiento_grupos')->nullOnDelete();
            $table->unique(
                ['establecimiento_periodo_id', 'alumno_id', 'establecimiento_asignatura_id', 'ciclo_orden', 'parcial_numero'],
                'est_calif_uk',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_calificaciones');
        Schema::dropIfExists('establecimiento_grupo_alumnos');
        Schema::dropIfExists('establecimiento_grupos');
        Schema::dropIfExists('establecimiento_alumno_asignaturas');
        Schema::dropIfExists('establecimiento_programa_alumnos');

        Schema::table('establecimiento_asignaturas', function (Blueprint $table) {
            $table->dropForeign('est_asig_prog_fk');
            $table->dropColumn(['es_flotante', 'escala', 'establecimiento_programa_id']);
        });

        Schema::dropIfExists('establecimiento_programas');
        Schema::dropIfExists('establecimiento_matriculas');
    }
};
