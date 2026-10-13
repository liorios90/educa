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
        Schema::table('enrollment_submissions', function (Blueprint $table): void {
            $table->foreignId('alumno_id')->nullable()->constrained('alumnos')->nullOnDelete();
            $table->date('periodo_inicio')->nullable();
            $table->date('periodo_fin')->nullable();
            $table->unique(['alumno_id', 'periodo_inicio', 'periodo_fin'], 'enrollment_submissions_student_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollment_submissions', function (Blueprint $table): void {
            $table->dropUnique('enrollment_submissions_student_period_unique');
            $table->dropConstrainedForeignId('alumno_id');
            $table->dropColumn(['periodo_inicio', 'periodo_fin']);
        });
    }
};
