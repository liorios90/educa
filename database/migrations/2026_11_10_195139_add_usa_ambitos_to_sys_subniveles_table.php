<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_subniveles', function (Blueprint $table) {
            $table->boolean('usa_ambitos')->default(false)->after('equivale_nota_nacional');
        });

        DB::table('sys_subniveles')
            ->where('califica_por_destrezas', true)
            ->where('equivale_nota_nacional', false)
            ->update(['usa_ambitos' => true]);
    }

    public function down(): void
    {
        Schema::table('sys_subniveles', function (Blueprint $table) {
            $table->dropColumn('usa_ambitos');
        });
    }
};
