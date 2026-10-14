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
        Schema::create('equivalencias_detalles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equivalencia_id')->constrained('equivalencias')->cascadeOnDelete();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->string('valor', 100)->nullable();
            $table->decimal('minimo', 12, 4)->nullable();
            $table->decimal('maximo', 12, 4)->nullable();
            $table->boolean('activo')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equivalencias_detalles');
    }
};
