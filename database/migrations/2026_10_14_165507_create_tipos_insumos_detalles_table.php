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
        Schema::create('tipos_insumos_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_insumo_id')->constrained('tipos_insumos')->cascadeOnDelete();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->string('valor', 100)->nullable();
            $table->decimal('porcentaje', 5, 2)->nullable();
            $table->boolean('activo')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_insumos_detalles');
    }
};
