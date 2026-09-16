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
        Schema::create('sys_zonas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion');
            $table->string('usuario');
            $table->smallInteger('activo');
            $table->timestamps();
        });

        Schema::create('sys_distritos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('provincia');
            $table->string('descripcion');
            $table->string('usuario');
            $table->smallInteger('activo');
            $table->foreignId('zona_id')
                ->constrained('sys_zonas')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('sys_circuitos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion');
            $table->string('usuario');
            $table->smallInteger('activo');
            $table->foreignId('distrito_id')
                ->nullable()
                ->constrained('sys_distritos')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_circuitos');
        Schema::dropIfExists('sys_distritos');
        Schema::dropIfExists('sys_zonas');
    }
};
