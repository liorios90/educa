<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('establecimientos', function (Blueprint $table): void {
            $table->unsignedTinyInteger('decimales_calificacion')->default(2)->after('ideario');
            $table->string('modo_decimal')->default('aproximar')->after('decimales_calificacion');
        });
    }

    public function down(): void
    {
        Schema::table('establecimientos', function (Blueprint $table): void {
            $table->dropColumn(['decimales_calificacion', 'modo_decimal']);
        });
    }
};
