<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')
                ->constrained('personas')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->string('contacto_emergencia');
            $table->string('usuario');
            $table->unsignedBigInteger('id_estructura_form_matricula')->default(0);
            $table->smallInteger('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnos');
    }
};
