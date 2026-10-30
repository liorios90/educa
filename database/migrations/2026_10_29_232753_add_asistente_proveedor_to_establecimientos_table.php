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
        Schema::table('establecimientos', function (Blueprint $table) {
            $table->string('asistente_proveedor', 20)->default('gemini')->after('gemini_model');
            $table->string('asistente_base_url')->nullable()->after('asistente_proveedor');
            $table->string('gemini_model', 200)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * gemini_model se queda en 200 caracteres: acortarla puede cortar nombres ya guardados.
     */
    public function down(): void
    {
        Schema::table('establecimientos', function (Blueprint $table) {
            $table->dropColumn(['asistente_proveedor', 'asistente_base_url']);
        });
    }
};
