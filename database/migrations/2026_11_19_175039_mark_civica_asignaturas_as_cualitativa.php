<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cívica es un periodo pedagógico cualitativo. Acuerdo MINEDEC-MINEDEC-2026-00082-A, artículo 9.
     */
    public function up(): void
    {
        DB::table('sys_asignaturas')
            ->where('nombre', 'Cívica y Acompañamiento Integral en el Aula')
            ->update([
                'tipo_calificacion' => 'cualitativa',
                'usa_criterios' => false,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sys_asignaturas')
            ->where('nombre', 'Cívica y Acompañamiento Integral en el Aula')
            ->update([
                'tipo_calificacion' => 'cuantitativa',
                'usa_criterios' => false,
            ]);
    }
};
