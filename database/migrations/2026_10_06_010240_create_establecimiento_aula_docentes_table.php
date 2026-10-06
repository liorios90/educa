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
        Schema::create('establecimiento_aula_docentes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_aula_id');
            $table->unsignedBigInteger('establecimiento_asignatura_id');
            $table->unsignedBigInteger('empleado_id');
            $table->timestamps();

            $table->foreign('establecimiento_aula_id', 'est_aula_doc_aula_fk')
                ->references('id')
                ->on('establecimiento_aulas')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_asignatura_id', 'est_aula_doc_malla_fk')
                ->references('id')
                ->on('establecimiento_asignaturas')
                ->cascadeOnDelete();
            $table->foreign('empleado_id', 'est_aula_doc_emp_fk')
                ->references('id')
                ->on('empleados')
                ->cascadeOnDelete();

            $table->unique(
                ['establecimiento_aula_id', 'establecimiento_asignatura_id'],
                'est_aula_doc_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimiento_aula_docentes');
    }
};
