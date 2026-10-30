<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Some MySQL tables were created with an integer primary key and no AUTO_INCREMENT.
 * Inserts then fail with "Field 'id' doesn't have a default value".
 * SQLite assigns row ids on its own, so the repair only runs on MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->reparar();
    }

    /**
     * Keeps the repaired primary keys. The original schema already requires AUTO_INCREMENT.
     */
    public function down(): void {}

    private function reparar(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $columnas = DB::select("
            select table_name, column_type, extra
            from information_schema.columns
            where table_schema = database()
              and column_name = 'id'
              and column_key = 'PRI'
        ");

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($columnas as $columna) {
                $datos = array_change_key_case(get_object_vars($columna), CASE_LOWER);
                $tabla = (string) $datos['table_name'];
                $tipo = strtolower((string) $datos['column_type']);
                $extra = strtolower((string) ($datos['extra'] ?? ''));

                if (str_contains($extra, 'auto_increment')) {
                    continue;
                }

                if (! preg_match('/^[a-z0-9_]+$/', $tabla) || ! preg_match('/^(tinyint|smallint|mediumint|int|bigint)( unsigned)?$/', $tipo)) {
                    continue;
                }

                DB::statement("alter table `{$tabla}` modify `id` {$tipo} not null auto_increment");
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};
