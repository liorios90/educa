<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('establecimiento_periodos', function (Blueprint $table) {
            $table->string('modo_ponderacion_ciclos')->default('individual')->after('numero_parciales');
            $table->decimal('porcentaje_promedio_ciclos', 5, 2)->nullable()->after('modo_ponderacion_ciclos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_periodos', function (Blueprint $table) {
            $table->dropColumn(['modo_ponderacion_ciclos', 'porcentaje_promedio_ciclos']);
        });
    }
};
