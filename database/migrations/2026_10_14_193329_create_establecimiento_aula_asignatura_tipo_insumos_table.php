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
        if (Schema::hasTable('establecimiento_aula_asignatura_tipo_insumos')) {
            Schema::table('establecimiento_aula_asignatura_tipo_insumos', function (Blueprint $table): void {
                $this->agregarClavesYUnicidad($table);
            });

            return;
        }

        Schema::create('establecimiento_aula_asignatura_tipo_insumos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_aula_id');
            $table->unsignedBigInteger('establecimiento_asignatura_id');
            $table->unsignedBigInteger('tipo_insumo_id');
            $table->timestamps();

            $this->agregarClavesYUnicidad($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_aula_asignatura_tipo_insumos');
    }

    private function agregarClavesYUnicidad(Blueprint $table): void
    {
        $table->foreign('establecimiento_aula_id', 'est_aula_asig_tipo_aula_fk')
            ->references('id')
            ->on('establecimiento_aulas')
            ->cascadeOnDelete();
        $table->foreign('establecimiento_asignatura_id', 'est_aula_asig_tipo_asig_fk')
            ->references('id')
            ->on('establecimiento_asignaturas')
            ->cascadeOnDelete();
        $table->foreign('tipo_insumo_id', 'est_aula_asig_tipo_tipo_fk')
            ->references('id')
            ->on('tipos_insumos')
            ->cascadeOnDelete();
        $table->unique(
            ['establecimiento_aula_id', 'establecimiento_asignatura_id'],
            'est_aula_asig_tipo_unique',
        );
    }
};
