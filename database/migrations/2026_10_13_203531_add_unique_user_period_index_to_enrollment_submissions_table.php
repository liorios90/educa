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
        $indexName = 'enrollment_submissions_form_user_unique';

        if (! Schema::hasIndex('enrollment_submissions', $indexName)) {
            Schema::table('enrollment_submissions', function (Blueprint $table) use ($indexName): void {
                $table->unique(['enrollment_form_id', 'user_id'], $indexName);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexName = 'enrollment_submissions_form_user_unique';

        if (Schema::hasIndex('enrollment_submissions', $indexName)) {
            Schema::table('enrollment_submissions', function (Blueprint $table) use ($indexName): void {
                $table->dropUnique($indexName);
            });
        }
    }
};
