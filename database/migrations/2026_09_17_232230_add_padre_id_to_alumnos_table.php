<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $table->foreignId('padre_id')
                ->nullable()
                ->constrained('padres')
                ->restrictOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('padre_id');
        });
    }
};
