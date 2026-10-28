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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establecimiento_id')
                ->nullable()
                ->constrained('establecimientos')
                ->nullOnDelete();
            $table->foreignId('remitente_id')->constrained('users')->cascadeOnDelete();
            $table->string('asunto', 160);
            $table->text('cuerpo');
            $table->timestamps();

            $table->index(['establecimiento_id', 'created_at']);
            $table->index(['remitente_id', 'created_at']);
        });

        Schema::create('notificacion_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notificacion_id')->constrained('notificaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('leida_at')->nullable();
            $table->timestamps();

            $table->unique(['notificacion_id', 'user_id'], 'notificacion_destinatario_uk');
            $table->index(['user_id', 'leida_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificacion_destinatarios');
        Schema::dropIfExists('notificaciones');
    }
};
