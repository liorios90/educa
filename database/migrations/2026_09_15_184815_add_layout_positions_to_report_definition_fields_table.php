<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_definition_fields', function (Blueprint $table) {
            $table->unsignedTinyInteger('label_x')->default(4);
            $table->unsignedTinyInteger('label_y')->default(8);
            $table->unsignedTinyInteger('value_x')->default(32);
            $table->unsignedTinyInteger('value_y')->default(8);
        });
    }

    public function down(): void
    {
        Schema::table('report_definition_fields', function (Blueprint $table) {
            $table->dropColumn(['label_x', 'label_y', 'value_x', 'value_y']);
        });
    }
};
