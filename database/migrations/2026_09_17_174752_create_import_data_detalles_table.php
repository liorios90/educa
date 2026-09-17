<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_data_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_data_id')
                ->constrained('import_datas')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->integer('num_fila')->nullable();
            $table->string('identificacion')->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_data_detalles');
    }
};
