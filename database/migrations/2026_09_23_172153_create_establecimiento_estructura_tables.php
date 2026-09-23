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
        Schema::create('establecimiento_niveles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establecimiento_id')
                ->constrained('establecimientos')
                ->cascadeOnDelete();
            $table->foreignId('nivel_id')
                ->constrained('sys_niveles')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(['establecimiento_id', 'nivel_id']);
        });

        Schema::create('establecimiento_subniveles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establecimiento_id')
                ->constrained('establecimientos')
                ->cascadeOnDelete();
            $table->foreignId('nivel_id')
                ->constrained('sys_niveles')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('subnivel_id')
                ->constrained('sys_subniveles')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(['establecimiento_id', 'subnivel_id']);
        });

        Schema::create('establecimiento_grados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establecimiento_id')
                ->constrained('establecimientos')
                ->cascadeOnDelete();
            $table->foreignId('subnivel_id')
                ->constrained('sys_subniveles')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('grado_id')
                ->constrained('sys_grados')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(['establecimiento_id', 'grado_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_grados');
        Schema::dropIfExists('establecimiento_subniveles');
        Schema::dropIfExists('establecimiento_niveles');
    }
};
