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
            $table->unsignedInteger('gemini_preguntas_limite')->default(100);
            $table->unsignedInteger('gemini_tokens_limite')->default(100000);
            $table->unsignedInteger('gemini_preguntas_usadas')->default(0);
            $table->unsignedInteger('gemini_tokens_usados')->default(0);
            $table->string('gemini_uso_mes', 7)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimientos', function (Blueprint $table) {
            $table->dropColumn([
                'gemini_preguntas_limite',
                'gemini_tokens_limite',
                'gemini_preguntas_usadas',
                'gemini_tokens_usados',
                'gemini_uso_mes',
            ]);
        });
    }
};
