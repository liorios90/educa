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
        // Keep a standalone index for the establecimiento_id foreign key
        // before removing the unique index that currently supports it.
        $periodIndex = 'enrollment_forms_school_period_idx';
        if (! Schema::hasIndex('enrollment_forms', $periodIndex)) {
            Schema::table('enrollment_forms', function (Blueprint $table) use ($periodIndex): void {
                $table->index(['establecimiento_id', 'periodo_inicio', 'periodo_fin'], $periodIndex);
            });
        }

        $periodUnique = 'enrollment_forms_school_period_unique';
        if (Schema::hasIndex('enrollment_forms', $periodUnique)) {
            Schema::table('enrollment_forms', function (Blueprint $table) use ($periodUnique): void {
                $table->dropUnique($periodUnique);
            });
        }

        $submissionUnique = 'enrollment_submissions_form_user_unique';
        if (Schema::hasIndex('enrollment_submissions', $submissionUnique)) {
            Schema::table('enrollment_submissions', function (Blueprint $table) use ($submissionUnique): void {
                $table->dropUnique($submissionUnique);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasIndex('enrollment_forms', 'enrollment_forms_establecimiento_id_unique')) {
            Schema::table('enrollment_forms', function (Blueprint $table): void {
                $table->unique('establecimiento_id', 'enrollment_forms_establecimiento_id_unique');
            });
        }

        $periodIndex = 'enrollment_forms_school_period_idx';
        if (Schema::hasIndex('enrollment_forms', $periodIndex)) {
            Schema::table('enrollment_forms', function (Blueprint $table) use ($periodIndex): void {
                $table->dropIndex($periodIndex);
            });
        }
    }
};
