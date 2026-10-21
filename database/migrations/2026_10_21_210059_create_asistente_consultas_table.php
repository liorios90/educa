<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistente_consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establecimiento_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('usuario');
            $table->text('pregunta');
            $table->timestamps();

            $table->index(['created_at', 'id'], 'asistente_consultas_fecha_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistente_consultas');
    }
};
