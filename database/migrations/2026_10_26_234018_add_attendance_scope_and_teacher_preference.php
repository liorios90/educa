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

        $this->reemplazarUnicoAsistencia(
            'est_asistencias_alumno_asignatura_fecha_unq',
            'est_asistencias_alumno_aula_fecha_clave_unq',
            ['alumno_id', 'establecimiento_aula_id', 'fecha', 'clave_asignatura'],
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->reemplazarUnicoAsistencia(
            'est_asistencias_alumno_aula_fecha_clave_unq',
            'est_asistencias_alumno_asignatura_fecha_unq',
            ['alumno_id', 'establecimiento_asignatura_id', 'fecha'],
        );

        Schema::table('establecimiento_asistencias', function (Blueprint $table) {
            $table->dropColumn(['clave_asignatura', 'alcance']);
        });

        Schema::table('establecimiento_aula_docentes', function (Blueprint $table) {
            $table->dropColumn('modo_asistencia');
        });
    }

    /**
     * En MySQL el único (alumno_id, ...) sostiene la foránea de alumno_id.
     * Hay que crear otro índice sobre alumno_id antes de soltarlo.
     *
     * @param  list<string>  $columnasSiguiente
     */
    private function reemplazarUnicoAsistencia(string $anterior, string $siguiente, array $columnasSiguiente): void
    {
        $esMysql = Schema::getConnection()->getDriverName() !== 'sqlite';

        if ($esMysql && ! Schema::hasIndex('establecimiento_asistencias', 'est_asistencias_alumno_id_idx')) {
            Schema::table('establecimiento_asistencias', function (Blueprint $table): void {
                $table->index('alumno_id', 'est_asistencias_alumno_id_idx');
            });
        }

        if (Schema::hasIndex('establecimiento_asistencias', $anterior)) {
            Schema::table('establecimiento_asistencias', function (Blueprint $table) use ($anterior): void {
                $table->dropUnique($anterior);
            });
        }

        if (! Schema::hasIndex('establecimiento_asistencias', $siguiente)) {
            Schema::table('establecimiento_asistencias', function (Blueprint $table) use ($siguiente, $columnasSiguiente): void {
                $table->unique($columnasSiguiente, $siguiente);
            });
        }
    }
};
