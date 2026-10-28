<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The behavior columns are added by the later hierarchy migration,
     * which also covers databases that already ran this empty step.
     */
    public function up(): void {}

    public function down(): void {}
};
