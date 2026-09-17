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
        Schema::create('sys_subniveles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->string('descripcion', 150)->nullable();
            $table->foreignId('nivel_id')
                ->nullable()
                ->constrained('sys_niveles')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('sys_grados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->string('descripcion', 150)->nullable();
            $table->foreignId('subnivel_id')
                ->nullable()
                ->constrained('sys_subniveles')
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_grados');
        Schema::dropIfExists('sys_subniveles');
    }
};
