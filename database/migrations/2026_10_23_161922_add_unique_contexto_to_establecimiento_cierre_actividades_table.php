<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('establecimiento_cierre_actividades', function (Blueprint $table): void {
            $table->unique(
                ['establecimiento_aula_id', 'establecimiento_asignatura_id', 'establecimiento_periodo_ciclo_id', 'tipo'],
                'est_cierre_act_contexto_uk',
            );
        });
    }

    public function down(): void
    {
        Schema::table('establecimiento_cierre_actividades', function (Blueprint $table): void {
            $table->dropUnique('est_cierre_act_contexto_uk');
        });
    }
};
