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
        Schema::table('sys_subniveles', function (Blueprint $table) {
            $table->boolean('califica_por_destrezas')->default(false)->after('tipo_calificacion');
        });

        Schema::table('establecimiento_asignaturas', function (Blueprint $table) {
            $table->boolean('califica_por_destrezas')->default(false)->after('tipo_calificacion');
            $table->boolean('usa_tabla_equivalencias')->default(false)->after('califica_por_destrezas');
        });

        $elementalIds = DB::table('sys_subniveles')
            ->where('nombre', 'Básica Elemental')
            ->orWhere('siglas', 'BE')
            ->pluck('id');

        DB::table('sys_subniveles')
            ->where('tipo_calificacion', 'destrezas')
            ->update([
                'tipo_calificacion' => 'cualitativa',
                'califica_por_destrezas' => true,
            ]);

        if ($elementalIds->isNotEmpty()) {
            DB::table('sys_subniveles')
                ->where('tipo_calificacion', 'calificacion')
                ->whereIn('id', $elementalIds)
                ->update([
                    'tipo_calificacion' => 'cualitativa',
                    'califica_por_destrezas' => false,
                ]);
        }

        DB::table('sys_subniveles')
            ->where('tipo_calificacion', 'calificacion')
            ->update([
                'tipo_calificacion' => 'cuantitativa',
                'califica_por_destrezas' => false,
            ]);

        DB::table('establecimiento_asignaturas')
            ->where('tipo_calificacion', 'destrezas')
            ->update([
                'tipo_calificacion' => 'cualitativa',
                'califica_por_destrezas' => true,
                'usa_tabla_equivalencias' => false,
            ]);

        $tiposPorGrado = DB::table('sys_grados')
            ->join('sys_subniveles', 'sys_subniveles.id', '=', 'sys_grados.subnivel_id')
            ->pluck('sys_subniveles.tipo_calificacion', 'sys_grados.id');

        $pendientes = DB::table('establecimiento_asignaturas')
            ->where('tipo_calificacion', 'calificacion')
            ->get(['id', 'grado_id']);

        foreach ($pendientes as $fila) {
            $tipo = $tiposPorGrado[$fila->grado_id] ?? 'cuantitativa';

            if (! in_array($tipo, ['cualitativa', 'cuantitativa'], true)) {
                $tipo = 'cuantitativa';
            }

            DB::table('establecimiento_asignaturas')
                ->where('id', $fila->id)
                ->update([
                    'tipo_calificacion' => $tipo,
                    'califica_por_destrezas' => false,
                    'usa_tabla_equivalencias' => false,
                ]);
        }

        Schema::table('sys_subniveles', function (Blueprint $table) {
            $table->string('tipo_calificacion', 20)->default('cuantitativa')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sys_subniveles')
            ->where('tipo_calificacion', 'cualitativa')
            ->where('califica_por_destrezas', true)
            ->update(['tipo_calificacion' => 'destrezas']);

        DB::table('sys_subniveles')
            ->whereIn('tipo_calificacion', ['cualitativa', 'cuantitativa'])
            ->update(['tipo_calificacion' => 'calificacion']);

        DB::table('establecimiento_asignaturas')
            ->where('tipo_calificacion', 'cualitativa')
            ->where('califica_por_destrezas', true)
            ->update(['tipo_calificacion' => 'destrezas']);

        DB::table('establecimiento_asignaturas')
            ->whereIn('tipo_calificacion', ['cualitativa', 'cuantitativa'])
            ->update(['tipo_calificacion' => 'calificacion']);

        Schema::table('sys_subniveles', function (Blueprint $table) {
            $table->string('tipo_calificacion', 20)->default('calificacion')->change();
            $table->dropColumn('califica_por_destrezas');
        });

        Schema::table('establecimiento_asignaturas', function (Blueprint $table) {
            $table->dropColumn(['califica_por_destrezas', 'usa_tabla_equivalencias']);
        });
    }
};
