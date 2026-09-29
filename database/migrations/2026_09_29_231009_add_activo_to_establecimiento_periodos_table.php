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
        Schema::table('establecimiento_periodos', function (Blueprint $table) {
            $table->boolean('activo')->default(false);
        });

        $ofertaIds = DB::table('establecimiento_periodos')
            ->distinct()
            ->pluck('establecimiento_modalidad_jornada_id');

        foreach ($ofertaIds as $ofertaId) {
            $periodoId = DB::table('establecimiento_periodos')
                ->where('establecimiento_modalidad_jornada_id', $ofertaId)
                ->orderByDesc('id')
                ->value('id');

            if ($periodoId === null) {
                continue;
            }

            DB::table('establecimiento_periodos')
                ->where('id', $periodoId)
                ->update(['activo' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_periodos', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
