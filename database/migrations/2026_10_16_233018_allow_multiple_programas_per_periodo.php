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
        Schema::table('establecimiento_programas', function (Blueprint $table) {
            $table->index('establecimiento_modalidad_jornada_id', 'est_prog_oferta_idx');
        });

        Schema::table('establecimiento_programas', function (Blueprint $table) {
            $table->dropUnique('est_prog_oferta_periodo_tipo_uk');
            $table->unique(
                ['establecimiento_modalidad_jornada_id', 'establecimiento_periodo_id', 'nombre'],
                'est_prog_oferta_periodo_nombre_uk',
            );
        });

        Schema::table('establecimiento_programas', function (Blueprint $table) {
            $table->dropIndex('est_prog_oferta_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_programas', function (Blueprint $table) {
            $table->index('establecimiento_modalidad_jornada_id', 'est_prog_oferta_idx');
        });

        Schema::table('establecimiento_programas', function (Blueprint $table) {
            $table->dropUnique('est_prog_oferta_periodo_nombre_uk');
            $table->unique(
                ['establecimiento_modalidad_jornada_id', 'establecimiento_periodo_id', 'tipo'],
                'est_prog_oferta_periodo_tipo_uk',
            );
        });

        Schema::table('establecimiento_programas', function (Blueprint $table) {
            $table->dropIndex('est_prog_oferta_idx');
        });
    }
};
