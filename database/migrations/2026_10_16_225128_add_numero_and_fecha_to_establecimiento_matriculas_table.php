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
        Schema::table('establecimiento_matriculas', function (Blueprint $table) {
            $table->string('numero', 40)->nullable()->after('alumno_id');
            $table->unsignedInteger('secuencia')->nullable()->after('numero');
            $table->date('fecha')->nullable()->after('secuencia');
        });

        $contadores = [];

        foreach (DB::table('establecimiento_matriculas')->orderBy('id')->get() as $fila) {
            $clave = $fila->establecimiento_id.'-'.$fila->establecimiento_periodo_id;
            $contadores[$clave] = ($contadores[$clave] ?? 0) + 1;
            $secuencia = $contadores[$clave];

            DB::table('establecimiento_matriculas')->where('id', $fila->id)->update([
                'secuencia' => $secuencia,
                'numero' => sprintf('E%d-%d-%04d', $fila->establecimiento_id, $fila->establecimiento_periodo_id, $secuencia),
                'fecha' => $fila->created_at !== null ? substr((string) $fila->created_at, 0, 10) : now()->toDateString(),
            ]);
        }

        Schema::table('establecimiento_matriculas', function (Blueprint $table) {
            $table->unique('numero', 'est_mat_numero_uk');
            $table->unique(['establecimiento_id', 'establecimiento_periodo_id', 'secuencia'], 'est_mat_secuencia_uk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_matriculas', function (Blueprint $table) {
            $table->dropUnique('est_mat_numero_uk');
            $table->dropUnique('est_mat_secuencia_uk');
            $table->dropColumn(['numero', 'secuencia', 'fecha']);
        });
    }
};
