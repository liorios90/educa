<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('source');
            $table->boolean('is_active')->default(true);
            $table->boolean('visible_to_all')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'source']);
        });

        Schema::create('report_definition_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_definition_id')->constrained('report_definitions')->cascadeOnDelete();
            $table->string('column');
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['report_definition_id', 'sort_order']);
        });

        Schema::create('report_definition_role', function (Blueprint $table) {
            $table->foreignId('report_definition_id')->constrained('report_definitions')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();

            $table->primary(['report_definition_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_definition_role');
        Schema::dropIfExists('report_definition_fields');
        Schema::dropIfExists('report_definitions');
    }
};
