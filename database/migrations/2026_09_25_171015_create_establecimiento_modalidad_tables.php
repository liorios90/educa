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
        Schema::create('establecimiento_modalidades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_id');
            $table->unsignedBigInteger('modalidad_id');
            $table->timestamps();

            $table->foreign('establecimiento_id', 'est_mod_est_fk')
                ->references('id')
                ->on('establecimientos')
                ->cascadeOnDelete();
            $table->foreign('modalidad_id', 'est_mod_mod_fk')
                ->references('id')
                ->on('sys_modalidades')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->unique(['establecimiento_id', 'modalidad_id'], 'est_mod_unique');
        });

        Schema::create('establecimiento_modalidad_jornadas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_modalidad_id');
            $table->unsignedBigInteger('jornada_id');
            $table->timestamps();

            $table->foreign('establecimiento_modalidad_id', 'est_mj_em_fk')
                ->references('id')
                ->on('establecimiento_modalidades')
                ->cascadeOnDelete();
            $table->foreign('jornada_id', 'est_mj_jor_fk')
                ->references('id')
                ->on('sys_jornadas')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->unique(['establecimiento_modalidad_id', 'jornada_id'], 'est_mj_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_modalidad_jornadas');
        Schema::dropIfExists('establecimiento_modalidades');
    }
};
