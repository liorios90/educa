<?php

use Database\Seeders\CriterioComportamientoSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_criterios_comportamiento', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('descripcion', 500);
            $table->string('tipo', 20);
            $table->boolean('activo')->default(true);
            $table->boolean('obligatorio')->default(false);
            $table->string('usuario');
            $table->timestamps();
        });

        (new CriterioComportamientoSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_criterios_comportamiento');
    }
};
