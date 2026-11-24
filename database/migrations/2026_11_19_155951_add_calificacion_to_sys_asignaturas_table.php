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
        Schema::table('sys_asignaturas', function (Blueprint $table) {
            $table->string('tipo_calificacion')->default('cuantitativa');
            $table->boolean('usa_criterios')->default(false);
        });

        $areasDeAmbitos = DB::table('sys_areas')
            ->select('sys_areas.id')
            ->join('sys_subniveles', 'sys_subniveles.id', '=', 'sys_areas.subnivel_id')
            ->where('sys_subniveles.usa_ambitos', true);

        DB::table('sys_asignaturas')
            ->whereIn('area_id', $areasDeAmbitos)
            ->update([
                'tipo_calificacion' => 'cualitativa',
                'usa_criterios' => true,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sys_asignaturas', function (Blueprint $table) {
            $table->dropColumn(['tipo_calificacion', 'usa_criterios']);
        });
    }
};
