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
            $table->unsignedInteger('ultima_secuencia_matricula')->default(0)->after('abierto_en');
        });

        $maximos = DB::table('establecimiento_matriculas')
            ->select('establecimiento_periodo_id', DB::raw('MAX(secuencia) as ultima'))
            ->groupBy('establecimiento_periodo_id')
            ->get();

        foreach ($maximos as $maximo) {
            DB::table('establecimiento_periodos')
                ->where('id', $maximo->establecimiento_periodo_id)
                ->update(['ultima_secuencia_matricula' => (int) $maximo->ultima]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_periodos', function (Blueprint $table) {
            $table->dropColumn('ultima_secuencia_matricula');
        });
    }
};
