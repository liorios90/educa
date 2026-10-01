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
        Schema::create('establecimiento_aulas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_id');
            $table->unsignedBigInteger('establecimiento_modalidad_jornada_id');
            $table->unsignedBigInteger('establecimiento_periodo_id');
            $table->unsignedBigInteger('establecimiento_grado_id');
            $table->string('paralelo', 10);
            $table->timestamps();

            $table->foreign('establecimiento_id', 'est_aula_est_fk')
                ->references('id')
                ->on('establecimientos')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_modalidad_jornada_id', 'est_aula_oferta_fk')
                ->references('id')
                ->on('establecimiento_modalidad_jornadas')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_periodo_id', 'est_aula_per_fk')
                ->references('id')
                ->on('establecimiento_periodos')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_grado_id', 'est_aula_gra_fk')
                ->references('id')
                ->on('establecimiento_grados')
                ->cascadeOnDelete();

            $table->unique(
                ['establecimiento_periodo_id', 'establecimiento_grado_id', 'paralelo'],
                'est_aula_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_aulas');
    }
};
