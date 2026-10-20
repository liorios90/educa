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
        Schema::create('establecimiento_figuras_profesionales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_id');
            $table->unsignedBigInteger('establecimiento_modalidad_jornada_id');
            $table->string('nombre', 150);
            $table->timestamps();

            $table->foreign('establecimiento_id', 'est_fig_prof_est_fk')
                ->references('id')->on('establecimientos')->cascadeOnDelete();
            $table->foreign('establecimiento_modalidad_jornada_id', 'est_fig_prof_oferta_fk')
                ->references('id')->on('establecimiento_modalidad_jornadas')->cascadeOnDelete();
            $table->unique(
                ['establecimiento_modalidad_jornada_id', 'nombre'],
                'est_fig_prof_oferta_nombre_uk',
            );
        });

        Schema::table('establecimiento_asignaturas_propias', function (Blueprint $table): void {
            $table->unsignedBigInteger('figura_profesional_id')->nullable();
            $table->foreign('figura_profesional_id', 'est_asig_prop_fig_fk')
                ->references('id')->on('establecimiento_figuras_profesionales')->nullOnDelete();
        });

        Schema::table('establecimiento_matriculas', function (Blueprint $table): void {
            $table->unsignedBigInteger('figura_profesional_id')->nullable();
            $table->foreign('figura_profesional_id', 'est_mat_fig_fk')
                ->references('id')->on('establecimiento_figuras_profesionales')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_matriculas', function (Blueprint $table): void {
            $table->dropForeign('est_mat_fig_fk');
            $table->dropColumn('figura_profesional_id');
        });

        Schema::table('establecimiento_asignaturas_propias', function (Blueprint $table): void {
            $table->dropForeign('est_asig_prop_fig_fk');
            $table->dropColumn('figura_profesional_id');
        });

        Schema::dropIfExists('establecimiento_figuras_profesionales');
    }
};
