<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('padres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')
                ->constrained('personas')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->string('estado_civil_id');
            $table->smallInteger('vive_con_estudiante');
            $table->string('titulo');
            $table->string('usuario');
            $table->smallInteger('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('padres');
    }
};
