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
        Schema::table('establecimiento_asignaturas', function (Blueprint $table): void {
            $table->unsignedBigInteger('figura_profesional_id')->nullable();
            $table->foreign('figura_profesional_id', 'est_asig_fig_prof_fk')
                ->references('id')->on('establecimiento_figuras_profesionales')->nullOnDelete();
        });

        $filasExistentes = DB::table('establecimiento_asignaturas')
            ->join('establecimiento_asignaturas_propias', 'establecimiento_asignaturas_propias.id', '=', 'establecimiento_asignaturas.asignatura_propia_id')
            ->whereNotNull('establecimiento_asignaturas_propias.figura_profesional_id')
            ->select('establecimiento_asignaturas.id', 'establecimiento_asignaturas_propias.figura_profesional_id')
            ->get();

        foreach ($filasExistentes as $fila) {
            DB::table('establecimiento_asignaturas')
                ->where('id', $fila->id)
                ->update(['figura_profesional_id' => $fila->figura_profesional_id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_asignaturas', function (Blueprint $table): void {
            $table->dropForeign('est_asig_fig_prof_fk');
            $table->dropColumn('figura_profesional_id');
        });
    }
};
