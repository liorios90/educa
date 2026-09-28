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
        Schema::create('establecimiento_area_libretas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_id');
            $table->unsignedBigInteger('establecimiento_modalidad_jornada_id');
            $table->unsignedBigInteger('grado_id');
            $table->unsignedBigInteger('area_id');
            $table->string('modo_libreta', 30);
            $table->timestamps();

            $table->foreign('establecimiento_id', 'est_area_lib_est_fk')
                ->references('id')
                ->on('establecimientos')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_modalidad_jornada_id', 'est_area_lib_oferta_fk')
                ->references('id')
                ->on('establecimiento_modalidad_jornadas')
                ->cascadeOnDelete();
            $table->foreign('grado_id', 'est_area_lib_grado_fk')
                ->references('id')
                ->on('sys_grados')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->foreign('area_id', 'est_area_lib_area_fk')
                ->references('id')
                ->on('sys_areas')
                ->restrictOnUpdate()
                ->restrictOnDelete();

            $table->unique(
                ['establecimiento_modalidad_jornada_id', 'grado_id', 'area_id'],
                'est_area_lib_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_area_libretas');
    }
};
