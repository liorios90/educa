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
        $tabla = 'establecimiento_periodo_asignatura_ciclos';

        if (! Schema::hasTable($tabla)) {
            Schema::create($tabla, function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('establecimiento_periodo_id');
                $table->unsignedBigInteger('establecimiento_asignatura_id');
                $table->unsignedTinyInteger('ciclo_orden');
                $table->decimal('porcentaje', 5, 2);
                $table->timestamps();
            });
        }

        if (! Schema::hasForeignKey($tabla, 'est_per_asig_ciclo_per_fk')) {
            Schema::table($tabla, function (Blueprint $table): void {
                $table->foreign('establecimiento_periodo_id', 'est_per_asig_ciclo_per_fk')
                    ->references('id')
                    ->on('establecimiento_periodos')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasForeignKey($tabla, 'est_per_asig_ciclo_asig_fk')) {
            Schema::table($tabla, function (Blueprint $table): void {
                $table->foreign('establecimiento_asignatura_id', 'est_per_asig_ciclo_asig_fk')
                    ->references('id')
                    ->on('establecimiento_asignaturas')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasIndex($tabla, 'est_per_asig_ciclo_uk', 'unique')) {
            Schema::table($tabla, function (Blueprint $table): void {
                $table->unique(
                    ['establecimiento_periodo_id', 'establecimiento_asignatura_id', 'ciclo_orden'],
                    'est_per_asig_ciclo_uk',
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_periodo_asignatura_ciclos');
    }
};
