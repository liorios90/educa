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
        Schema::table('establecimiento_asignaturas_propias', function (Blueprint $table) {
            $table->string('tipo_asignatura', 40)->change();
        });

        Schema::table('establecimiento_asignaturas', function (Blueprint $table) {
            $table->string('tipo_asignatura', 40)->default('tronco_comun')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_asignaturas_propias', function (Blueprint $table) {
            $table->string('tipo_asignatura', 20)->change();
        });

        Schema::table('establecimiento_asignaturas', function (Blueprint $table) {
            $table->string('tipo_asignatura', 20)->default('tronco_comun')->change();
        });
    }
};
