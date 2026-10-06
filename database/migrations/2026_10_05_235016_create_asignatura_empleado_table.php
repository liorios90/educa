<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asignatura_empleado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->foreignId('asignatura_id')->constrained('sys_asignaturas')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['empleado_id', 'asignatura_id']);
        });

        if (Schema::hasColumn('empleados', 'asignatura_id')) {
            $ahora = now();

            DB::table('empleados')
                ->whereNotNull('asignatura_id')
                ->orderBy('id')
                ->lazy()
                ->each(function (object $empleado) use ($ahora): void {
                    DB::table('asignatura_empleado')->insert([
                        'empleado_id' => $empleado->id,
                        'asignatura_id' => $empleado->asignatura_id,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ]);
                });

            Schema::table('empleados', function (Blueprint $table) {
                $table->dropConstrainedForeignId('asignatura_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->foreignId('asignatura_id')
                ->nullable()
                ->after('funcion_id')
                ->constrained('sys_asignaturas')
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });

        DB::table('asignatura_empleado')
            ->select('empleado_id')
            ->selectRaw('min(asignatura_id) as asignatura_id')
            ->groupBy('empleado_id')
            ->orderBy('empleado_id')
            ->lazy()
            ->each(function (object $fila): void {
                DB::table('empleados')
                    ->where('id', $fila->empleado_id)
                    ->update(['asignatura_id' => $fila->asignatura_id]);
            });

        Schema::dropIfExists('asignatura_empleado');
    }
};
