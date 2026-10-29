<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establecimiento_tarea_entrega_archivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_tarea_entrega_id');
            $table->string('path');
            $table->string('nombre_original');
            $table->string('mime', 120);
            $table->unsignedInteger('size');
            $table->timestamps();

            $table->foreign('establecimiento_tarea_entrega_id', 'tarea_arch_entrega_fk')
                ->references('id')
                ->on('establecimiento_tarea_entregas')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimiento_tarea_entrega_archivos');
    }
};
