<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('navigation_items', function (Blueprint $table) {
            $table->string('group_display')
                ->default('screen')
                ->after('is_group');
        });
    }

    public function down(): void
    {
        Schema::table('navigation_items', function (Blueprint $table) {
            $table->dropColumn('group_display');
        });
    }
};
