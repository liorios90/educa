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
        Schema::table('establecimiento_periodos', function (Blueprint $table) {
            $table->string('nombre_esquema')->nullable()->after('esquema_ciclo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_periodos', function (Blueprint $table) {
            $table->dropColumn('nombre_esquema');
        });
    }
};
