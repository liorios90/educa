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
        Schema::table('sys_areas', function (Blueprint $table): void {
            $table->boolean('es_tecnica')->default(false)->after('aparece_en_libreta');
        });

        Schema::create('establecimiento_oferta_areas_tecnicas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('establecimiento_modalidad_jornada_id');
            $table->foreign('establecimiento_modalidad_jornada_id', 'est_oferta_area_tec_of_fk')
                ->references('id')->on('establecimiento_modalidad_jornadas')->cascadeOnDelete();
            $table->unsignedBigInteger('area_id');
            $table->foreign('area_id', 'est_oferta_area_tec_area_fk')
                ->references('id')->on('sys_areas')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['establecimiento_modalidad_jornada_id', 'area_id'], 'est_oferta_area_tec_uk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_oferta_areas_tecnicas');
        Schema::table('sys_areas', function (Blueprint $table): void {
            $table->dropColumn('es_tecnica');
        });
    }
};
