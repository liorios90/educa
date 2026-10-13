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
        $tabla = 'establecimiento_aula_asignatura_ciclos';

        if (! Schema::hasTable($tabla)) {
            Schema::create($tabla, function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('establecimiento_periodo_id');
                $table->unsignedBigInteger('establecimiento_aula_id');
                $table->unsignedBigInteger('establecimiento_asignatura_id');
                $table->unsignedTinyInteger('ciclo_orden');
                $table->decimal('porcentaje_insumos', 5, 2);
                $table->decimal('porcentaje_examen', 5, 2);
                $table->decimal('porcentaje_proyecto', 5, 2);
                $table->timestamps();
            });
        }

        if (! Schema::hasForeignKey($tabla, 'est_aula_asig_ciclo_per_fk')) {
            Schema::table($tabla, function (Blueprint $table): void {
                $table->foreign('establecimiento_periodo_id', 'est_aula_asig_ciclo_per_fk')
                    ->references('id')
                    ->on('establecimiento_periodos')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasForeignKey($tabla, 'est_aula_asig_ciclo_aula_fk')) {
            Schema::table($tabla, function (Blueprint $table): void {
                $table->foreign('establecimiento_aula_id', 'est_aula_asig_ciclo_aula_fk')
                    ->references('id')
                    ->on('establecimiento_aulas')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasForeignKey($tabla, 'est_aula_asig_ciclo_asig_fk')) {
            Schema::table($tabla, function (Blueprint $table): void {
                $table->foreign('establecimiento_asignatura_id', 'est_aula_asig_ciclo_asig_fk')
                    ->references('id')
                    ->on('establecimiento_asignaturas')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasIndex($tabla, 'est_aula_asig_ciclo_unique', 'unique')) {
            Schema::table($tabla, function (Blueprint $table): void {
                $table->unique(
                    ['establecimiento_periodo_id', 'establecimiento_aula_id', 'establecimiento_asignatura_id', 'ciclo_orden'],
                    'est_aula_asig_ciclo_unique',
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_aula_asignatura_ciclos');
    }
};
