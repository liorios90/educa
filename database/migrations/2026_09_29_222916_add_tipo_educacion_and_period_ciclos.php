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
        if (! Schema::hasColumn('establecimiento_periodos', 'intensivo')) {
            return;
        }

        Schema::table('establecimiento_periodos', function (Blueprint $table) {
            $table->dropColumn('intensivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('establecimiento_periodos', 'intensivo')) {
            return;
        }

        Schema::table('establecimiento_periodos', function (Blueprint $table) {
            $table->boolean('intensivo')->default(false);
        });
    }
};
