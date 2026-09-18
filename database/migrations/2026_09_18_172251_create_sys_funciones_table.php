<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_funciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion');
            $table->string('usuario');
            $table->smallInteger('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_funciones');
    }
};
