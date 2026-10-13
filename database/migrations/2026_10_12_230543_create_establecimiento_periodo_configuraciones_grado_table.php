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
        Schema::create('establecimiento_periodo_configuraciones_grado', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('establecimiento_periodo_id');
            $table->string('nombre', 120);
            $table->timestamps();

            $table->foreign('establecimiento_periodo_id', 'epcg_periodo_fk')
                ->references('id')->on('establecimiento_periodos')->cascadeOnDelete();
            $table->unique(['establecimiento_periodo_id', 'nombre'], 'epcg_periodo_nombre_uk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_periodo_configuraciones_grado');
    }
};
