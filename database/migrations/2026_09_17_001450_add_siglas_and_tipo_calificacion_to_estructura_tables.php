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
        Schema::table('sys_niveles', function (Blueprint $table) {
            $table->string('siglas', 10)->nullable();
        });

        Schema::table('sys_subniveles', function (Blueprint $table) {
            $table->string('siglas', 10)->nullable();
            $table->string('tipo_calificacion', 20)->default('calificacion');
        });

        Schema::table('sys_grados', function (Blueprint $table) {
            $table->string('siglas', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sys_grados', function (Blueprint $table) {
            $table->dropColumn('siglas');
        });

        Schema::table('sys_subniveles', function (Blueprint $table) {
            $table->dropColumn(['siglas', 'tipo_calificacion']);
        });

        Schema::table('sys_niveles', function (Blueprint $table) {
            $table->dropColumn('siglas');
        });
    }
};
