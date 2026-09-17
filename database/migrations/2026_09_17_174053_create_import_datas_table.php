<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_datas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tablas');
            $table->string('tipo_archivo');
            $table->text('mensaje');
            $table->foreignId('establecimiento_id')
                ->constrained('establecimientos')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->string('usuario');
            $table->smallInteger('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_datas');
    }
};
