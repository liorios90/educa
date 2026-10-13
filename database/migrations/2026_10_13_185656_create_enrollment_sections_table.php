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
        Schema::create('enrollment_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrollment_form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('enrollment_sections')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->index(['enrollment_form_id', 'parent_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_sections');
    }
};
