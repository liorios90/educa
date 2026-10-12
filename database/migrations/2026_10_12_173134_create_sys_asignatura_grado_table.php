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
        Schema::create('sys_asignatura_grado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignatura_id')->constrained('sys_asignaturas')->cascadeOnDelete();
            $table->foreignId('grado_id')->constrained('sys_grados')->cascadeOnDelete();
            $table->boolean('obligatoria')->default(false);
            $table->timestamps();
            $table->unique(['asignatura_id', 'grado_id']);
        });

        $obligatoriasPorCurso = [
            'Primero de Bachillerato' => [
                'Lengua y Literatura', 'Matemática', 'Física', 'Química', 'Biología',
                'Historia', 'Educación para la Ciudadanía', 'Filosofía',
                'Educación Cultural y Artística', 'Educación Física', 'Inglés', 'Emprendimiento y Gestión',
            ],
            'Segundo de Bachillerato' => [
                'Lengua y Literatura', 'Matemática', 'Física', 'Química', 'Biología',
                'Historia', 'Educación para la Ciudadanía', 'Filosofía',
                'Educación Cultural y Artística', 'Educación Física', 'Inglés', 'Emprendimiento y Gestión',
            ],
            'Tercero de Bachillerato' => [
                'Lengua y Literatura', 'Matemática', 'Física', 'Química', 'Biología',
                'Historia', 'Educación Física', 'Inglés', 'Emprendimiento y Gestión',
            ],
        ];

        $subnivelId = DB::table('sys_subniveles')
            ->where('nombre', 'Bachillerato General Unificado')
            ->value('id');

        if ($subnivelId === null) {
            return;
        }

        $asignaturas = DB::table('sys_asignaturas')
            ->join('sys_areas', 'sys_areas.id', '=', 'sys_asignaturas.area_id')
            ->where('sys_areas.subnivel_id', $subnivelId)
            ->get(['sys_asignaturas.id', 'sys_asignaturas.nombre']);
        $grados = DB::table('sys_grados')
            ->where('subnivel_id', $subnivelId)
            ->whereIn('nombre', array_keys($obligatoriasPorCurso))
            ->get(['id', 'nombre']);
        $filas = [];

        foreach ($grados as $grado) {
            foreach ($asignaturas as $asignatura) {
                $filas[] = [
                    'asignatura_id' => $asignatura->id,
                    'grado_id' => $grado->id,
                    'obligatoria' => in_array(
                        $asignatura->nombre,
                        $obligatoriasPorCurso[$grado->nombre],
                        true,
                    ),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($filas, 500) as $lote) {
            DB::table('sys_asignatura_grado')->insert($lote);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_asignatura_grado');
    }
};
