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
            $table->foreignId('equivalencia_id')
                ->nullable()
                ->after('usa_tabla_equivalencias')
                ->constrained('equivalencias')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimiento_asignaturas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equivalencia_id');
        });
    }
};
