<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')
                ->constrained('personas')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('tipo_contrato_id')
                ->constrained('sys_tipo_contratos')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->integer('cargo_id')->nullable()->index('empleados_cargo_id_foreign');
            $table->foreignId('funcion_id')
                ->nullable()
                ->constrained('sys_funciones')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->integer('horas');
            $table->integer('anios_experiencia');
            $table->integer('anios_instituto');
            $table->string('contacto_emergencia');
            $table->string('contacto_num');
            $table->string('usuario');
            $table->smallInteger('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
