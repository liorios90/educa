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
        Schema::create('sys_paises', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion');
            $table->string('usuario');
            $table->smallInteger('activo');
            $table->timestamps();
        });

        Schema::create('sys_provincias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion');
            $table->foreignId('pais_id')
                ->constrained('sys_paises')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->string('usuario');
            $table->smallInteger('activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_provincias');
        Schema::dropIfExists('sys_paises');
    }
};
