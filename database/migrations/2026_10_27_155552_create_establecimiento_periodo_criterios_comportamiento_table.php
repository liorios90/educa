<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('establecimiento_periodo_criterios_comportamiento');

        Schema::create('establecimiento_periodo_criterios_comportamiento', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_periodo_id');
            $table->unsignedBigInteger('criterio_comportamiento_id');
            $table->foreign('establecimiento_periodo_id', 'est_per_crit_comp_periodo_fk')
                ->references('id')
                ->on('establecimiento_periodos')
                ->cascadeOnDelete();
            $table->foreign('criterio_comportamiento_id', 'est_per_crit_comp_criterio_fk')
                ->references('id')
                ->on('sys_criterios_comportamiento')
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['establecimiento_periodo_id', 'criterio_comportamiento_id'],
                'est_periodo_criterio_comportamiento_unq',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimiento_periodo_criterios_comportamiento');
    }
};
