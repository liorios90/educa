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
        if (! Schema::hasTable('enrollment_submissions')) {
            Schema::create('enrollment_submissions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('enrollment_form_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('student_name')->nullable();
                $table->json('responses');
                $table->json('schema_snapshot');
                $table->timestamp('submitted_at')->useCurrent();
                $table->timestamps();
            });
        }

        $indexName = 'enrollment_submissions_form_user_submitted_idx';

        if (! Schema::hasIndex('enrollment_submissions', $indexName)) {
            Schema::table('enrollment_submissions', function (Blueprint $table) use ($indexName): void {
                $table->index(['enrollment_form_id', 'user_id', 'submitted_at'], $indexName);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_submissions');
    }
};
