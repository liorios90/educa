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
        if (! Schema::hasTable('establecimiento_aula_asignatura_equivalencias')) {
            Schema::create('establecimiento_aula_asignatura_equivalencias', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('establecimiento_aula_id');
                $table->unsignedBigInteger('establecimiento_asignatura_id');
                $table->unsignedBigInteger('equivalencia_id');
                $table->timestamps();
            });
        }

        Schema::table('establecimiento_aula_asignatura_equivalencias', function (Blueprint $table): void {
            $table->foreign('establecimiento_aula_id', 'est_aula_asig_equiv_aula_fk')
                ->references('id')->on('establecimiento_aulas')->cascadeOnDelete();
            $table->foreign('establecimiento_asignatura_id', 'est_aula_asig_equiv_asig_fk')
                ->references('id')->on('establecimiento_asignaturas')->cascadeOnDelete();
            $table->foreign('equivalencia_id', 'est_aula_asig_equiv_equiv_fk')
                ->references('id')->on('equivalencias')->cascadeOnDelete();
            $table->unique(['establecimiento_aula_id', 'establecimiento_asignatura_id'], 'est_aula_asig_equiv_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_aula_asignatura_equivalencias');
    }
};
