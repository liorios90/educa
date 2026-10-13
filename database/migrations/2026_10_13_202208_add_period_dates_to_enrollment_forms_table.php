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
        Schema::table('enrollment_forms', function (Blueprint $table): void {
            $table->date('periodo_inicio')->nullable();
            $table->date('periodo_fin')->nullable();
        });

        if (Schema::hasIndex('enrollment_forms', 'enrollment_forms_establecimiento_id_unique')) {
            Schema::table('enrollment_forms', function (Blueprint $table): void {
                $table->dropUnique('enrollment_forms_establecimiento_id_unique');
            });
        }

        DB::table('enrollment_forms')
            ->whereNull('periodo_inicio')
            ->orderBy('id')
            ->get(['id', 'establecimiento_id'])
            ->each(function (object $form): void {
                $period = DB::table('establecimiento_periodos')
                    ->where('establecimiento_id', $form->establecimiento_id)
                    ->orderByDesc('fecha_inicio')
                    ->orderByDesc('fecha_fin')
                    ->first(['fecha_inicio', 'fecha_fin']);

                if ($period !== null) {
                    DB::table('enrollment_forms')->where('id', $form->id)->update([
                        'periodo_inicio' => $period->fecha_inicio,
                        'periodo_fin' => $period->fecha_fin,
                    ]);
                }
            });

        if (! Schema::hasIndex('enrollment_forms', 'enrollment_forms_school_period_unique')) {
            Schema::table('enrollment_forms', function (Blueprint $table): void {
                $table->unique(
                    ['establecimiento_id', 'periodo_inicio', 'periodo_fin'],
                    'enrollment_forms_school_period_unique',
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasIndex('enrollment_forms', 'enrollment_forms_school_period_unique')) {
            Schema::table('enrollment_forms', function (Blueprint $table): void {
                $table->dropUnique('enrollment_forms_school_period_unique');
            });
        }

        Schema::table('enrollment_forms', function (Blueprint $table): void {
            $table->dropColumn(['periodo_inicio', 'periodo_fin']);
        });

        if (! Schema::hasIndex('enrollment_forms', 'enrollment_forms_establecimiento_id_unique')) {
            Schema::table('enrollment_forms', function (Blueprint $table): void {
                $table->unique('establecimiento_id');
            });
        }
    }
};
