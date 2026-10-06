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
        Schema::create('establecimiento_periodo_ciclo_parciales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_periodo_ciclo_id');
            $table->unsignedTinyInteger('numero');
            $table->timestamp('cerrado_en')->nullable();
            $table->timestamps();

            $table->unique(['establecimiento_periodo_ciclo_id', 'numero'], 'est_per_parc_ciclo_num_uk');
            $table->foreign('establecimiento_periodo_ciclo_id', 'est_per_parc_ciclo_fk')
                ->references('id')
                ->on('establecimiento_periodo_ciclos')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_periodo_ciclo_parciales');
    }
};
