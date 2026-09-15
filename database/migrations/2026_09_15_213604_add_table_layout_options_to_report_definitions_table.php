<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /** An earlier run of this migration was discarded with its file, so the columns may already exist. */
        if (Schema::hasColumn('report_definitions', 'layout')) {
            return;
        }

        Schema::table('report_definitions', function (Blueprint $table) {
            $table->string('layout')->default('canvas');
            $table->unsignedTinyInteger('table_border_width')->default(1);
            $table->string('table_border_color', 7)->default('#cbd5e1');
            $table->boolean('table_header')->default(true);
            $table->string('table_header_background', 7)->default('#f1f5f9');
            $table->boolean('table_striped')->default(false);
            $table->unsignedTinyInteger('table_font_size')->default(12);
            $table->unsignedTinyInteger('table_cell_padding')->default(8);
        });
    }

    public function down(): void
    {
        Schema::table('report_definitions', function (Blueprint $table) {
            $table->dropColumn([
                'layout',
                'table_border_width',
                'table_border_color',
                'table_header',
                'table_header_background',
                'table_striped',
                'table_font_size',
                'table_cell_padding',
            ]);
        });
    }
};
