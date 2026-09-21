<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_areas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->nullable();
            $table->string('nombre', 150);
            $table->string('descripcion', 255)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('aparece_en_libreta')->default(true);
            $table->foreignId('subnivel_id')
                ->constrained('sys_subniveles')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(['subnivel_id', 'nombre']);
        });

        Schema::create('sys_asignaturas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->nullable();
            $table->string('nombre', 150);
            $table->string('descripcion', 255)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->unsignedInteger('horas_semanales')->nullable();
            $table->boolean('aparece_en_libreta')->default(true);
            $table->foreignId('area_id')
                ->constrained('sys_areas')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(['area_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_asignaturas');
        Schema::dropIfExists('sys_areas');
    }
};
