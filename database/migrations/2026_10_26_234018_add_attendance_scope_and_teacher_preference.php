<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('establecimiento_aula_docentes', function (Blueprint $table) {
            $table->string('modo_asistencia', 20)->nullable()->after('empleado_id');
        });

        Schema::table('establecimiento_asistencias', function (Blueprint $table) {
            $table->unsignedBigInteger('clave_asignatura')->default(0)->after('establecimiento_asignatura_id');
            $table->string('alcance', 20)->default('asignatura')->after('fecha');
        });

        DB::table('establecimiento_asistencias')
            ->whereNotNull('establecimiento_asignatura_id')
            ->update([
                'alcance' => 'asignatura',
                'clave_asignatura' => DB::raw('establecimiento_asignatura_id'),
            ]);

        DB::table('establecimiento_asistencias')
            ->whereNull('establecimiento_asignatura_id')
            ->update([
                'alcance' => 'jornada',
                'clave_asignatura' => 0,
            ]);

        Schema::table('establecimiento_asistencias', function (Blueprint $table) {
            $table->dropUnique('est_asistencias_alumno_asignatura_fecha_unq');
            $table->unique(
                ['alumno_id', 'establecimiento_aula_id', 'fecha', 'clave_asignatura'],
                'est_asistencias_alumno_aula_fecha_clave_unq',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_asistencias', function (Blueprint $table) {
            $table->dropUnique('est_asistencias_alumno_aula_fecha_clave_unq');
            $table->unique(
                ['alumno_id', 'establecimiento_asignatura_id', 'fecha'],
                'est_asistencias_alumno_asignatura_fecha_unq',
            );
            $table->dropColumn(['clave_asignatura', 'alcance']);
        });

        Schema::table('establecimiento_aula_docentes', function (Blueprint $table) {
            $table->dropColumn('modo_asistencia');
        });
    }
};
