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
        Schema::table('establecimiento_subniveles', function (Blueprint $table) {
            $table->string('nombre', 150)->nullable();
        });

        Schema::table('establecimiento_grados', function (Blueprint $table) {
            $table->string('nombre', 150)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_grados', function (Blueprint $table) {
            $table->dropColumn('nombre');
        });

        Schema::table('establecimiento_subniveles', function (Blueprint $table) {
            $table->dropColumn('nombre');
        });
    }
};
