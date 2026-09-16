<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establecimientos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion');
            $table->string('direccion');
            $table->string('telefono');
            $table->string('representante');
            $table->string('codigo_amie')->unique();
            $table->text('regimen');
            $table->string('email')->unique();
            $table->string('usuario');
            $table->smallInteger('activo');
            $table->string('logo');
            $table->string('only_visible', 50)->nullable();
            $table->text('mision')->nullable();
            $table->text('vision')->nullable();
            $table->text('ideario')->nullable();
            $table->unsignedBigInteger('grupo_amie')->nullable();

            $table->foreignId('zona_id')
                ->constrained('sys_zonas')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('distrito_id')
                ->constrained('sys_distritos')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('circuito_id')
                ->constrained('sys_circuitos')
                ->restrictOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimientos');
    }
};
