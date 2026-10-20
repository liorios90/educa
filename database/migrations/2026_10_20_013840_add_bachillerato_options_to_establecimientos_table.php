<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('establecimientos', function (Blueprint $table): void {
            $table->boolean('bachillerato_tecnico')->default(false);
            $table->boolean('bachillerato_internacional')->default(false);
        });

        if (Schema::hasTable('establecimiento_figuras_profesionales')) {
            DB::table('establecimientos')
                ->whereIn('id', DB::table('establecimiento_figuras_profesionales')->select('establecimiento_id'))
                ->update(['bachillerato_tecnico' => true]);
        }

        if (Schema::hasTable('establecimiento_programas')) {
            DB::table('establecimientos')
                ->whereIn('id', DB::table('establecimiento_programas')
                    ->where('tipo', 'bachillerato_internacional')
                    ->select('establecimiento_id'))
                ->update(['bachillerato_internacional' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establecimientos', function (Blueprint $table): void {
            $table->dropColumn(['bachillerato_tecnico', 'bachillerato_internacional']);
        });
    }
};
