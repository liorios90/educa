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
        Schema::table('establecimiento_periodo_configuracion_grado_grados', function (Blueprint $table): void {
            $table->unsignedBigInteger('establecimiento_periodo_id')->nullable();
        });

        foreach (DB::table('establecimiento_periodo_configuracion_grado_grados')->orderBy('id')->cursor() as $grado) {
            $periodoId = DB::table('establecimiento_periodo_configuraciones_grado')
                ->where('id', $grado->configuracion_grado_id)
                ->value('establecimiento_periodo_id');

            DB::table('establecimiento_periodo_configuracion_grado_grados')
                ->where('id', $grado->id)
                ->update(['establecimiento_periodo_id' => $periodoId]);
        }

        Schema::table('establecimiento_periodo_configuracion_grado_grados', function (Blueprint $table): void {
            $table->foreign('establecimiento_periodo_id', 'epcgg_periodo_fk')
                ->references('id')->on('establecimiento_periodos')->cascadeOnDelete();
            $table->unique(['establecimiento_periodo_id', 'grado_id'], 'epcgg_periodo_grado_uk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_periodo_configuracion_grado_grados', function (Blueprint $table): void {
            $table->dropUnique('epcgg_periodo_grado_uk');
            $table->dropForeign('epcgg_periodo_fk');
            $table->dropColumn('establecimiento_periodo_id');
        });
    }
};
