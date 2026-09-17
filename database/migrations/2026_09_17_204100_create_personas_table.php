<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tipo_identificacion_id');
            $table->string('identificacion')->unique('idx_identificacion');
            $table->string('nombres');
            $table->string('apellidos');
            $table->unsignedBigInteger('genero_id');
            $table->date('fecha_nacimiento')->nullable();
            $table->string('ciudad_nacimiento');
            $table->foreignId('provincia_id')
                ->constrained('sys_provincias')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->string('parroquia');
            $table->string('direccion');
            $table->string('telefono1');
            $table->string('telefono2');
            $table->foreignId('nacionalidad_id')
                ->constrained('sys_paises')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->string('usuario');
            $table->smallInteger('activo');
            $table->foreignId('establecimiento_id')
                ->nullable()
                ->constrained('establecimientos')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->smallInteger('lote')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
