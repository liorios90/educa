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
        Schema::table('establecimiento_asignaturas', function (Blueprint $table) {
            $table->decimal('porcentaje', 5, 2)->nullable()->after('usa_tabla_equivalencias');
        });

        Schema::table('establecimiento_area_libretas', function (Blueprint $table) {
            $table->boolean('promedio_porcentual')->default(false)->after('modo_libreta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_asignaturas', function (Blueprint $table) {
            $table->dropColumn('porcentaje');
        });

        Schema::table('establecimiento_area_libretas', function (Blueprint $table) {
            $table->dropColumn('promedio_porcentual');
        });
    }
};
