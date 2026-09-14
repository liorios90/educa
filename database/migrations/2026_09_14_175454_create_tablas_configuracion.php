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

        Schema::create('sys_jornadas', function (Blueprint $table) {
            $table->id('id');
            // Matutina, Vespertina, Nocturna, Intensiva (Suflex/Fin de semana)
            $table->string('nombre', 50)->unique();
            $table->string('descripcion', 150)->nullable();
            $table->timestamps();
        });
        //
        Schema::create('sys_modalidades', function (Blueprint $table) {
            $table->id('id');
            // Presencial, Semipresencial, A distancia (Virtual)
            $table->string('nombre', 50)->unique();
            $table->string('descripcion', 150)->nullable();
            $table->timestamps();
        });
        //
        Schema::create('sys_niveles', function (Blueprint $table) {
            $table->id();
            // Presencial, Semipresencial, A distancia (Virtual)
            $table->string('nombre', 50)->unique();
            $table->string('descripcion', 150)->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_jornadas');
        Schema::dropIfExists('sys_modalidades');
        Schema::dropIfExists('sys_niveles');
    }
};
