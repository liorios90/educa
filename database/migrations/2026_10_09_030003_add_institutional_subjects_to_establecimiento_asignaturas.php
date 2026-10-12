<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('establecimiento_asignaturas_propias')) {
            $this->crearAsignaturasPropias();
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('establecimiento_asignaturas', function (Blueprint $table): void {
                $table->dropForeign(['asignatura_id']);
                $table->dropUnique('est_asig_unique');
            });
        } else {
            $this->liberarAsignaturaEnMysql();
        }

        Schema::table('establecimiento_asignaturas', function (Blueprint $table): void {
            if (! Schema::hasColumn('establecimiento_asignaturas', 'asignatura_propia_id')) {
                $table->unsignedBigInteger('asignatura_id')->nullable()->change();
                $table->unsignedBigInteger('asignatura_propia_id')->nullable()->after('asignatura_id');
                $table->string('tipo_asignatura', 20)->default('tronco_comun')->after('tipo_calificacion');
            }

            if (! $this->foraneaExiste('establecimiento_asignaturas', 'est_asig_asig_fk')) {
                $table->foreign('asignatura_id', 'est_asig_asig_fk')
                    ->references('id')
                    ->on('sys_asignaturas')
                    ->restrictOnUpdate()
                    ->restrictOnDelete();
            }

            if (! $this->foraneaExiste('establecimiento_asignaturas', 'est_asig_propia_fk')) {
                $table->foreign('asignatura_propia_id', 'est_asig_propia_fk')
                    ->references('id')
                    ->on('establecimiento_asignaturas_propias')
                    ->cascadeOnDelete();
            }

            if (! $this->indiceExiste('establecimiento_asignaturas', 'est_asig_unique')) {
                $table->unique(
                    ['establecimiento_modalidad_jornada_id', 'grado_id', 'asignatura_id'],
                    'est_asig_unique',
                );
            }

            if (! $this->indiceExiste('establecimiento_asignaturas', 'est_asig_propia_unique')) {
                $table->unique(
                    ['establecimiento_modalidad_jornada_id', 'grado_id', 'asignatura_propia_id'],
                    'est_asig_propia_unique',
                );
            }
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            $this->quitarIndiceTemporalOferta();
        }
    }

    /**
     * La oferta usa el índice único como soporte. Hay que crear otro índice
     * antes de quitarlo, y el índice de la asignatura queda huérfano al soltar la foránea.
     */
    private function liberarAsignaturaEnMysql(): void
    {
        if (! $this->indiceExiste('establecimiento_asignaturas', 'est_asig_oferta_idx')) {
            DB::statement('alter table establecimiento_asignaturas add index est_asig_oferta_idx (establecimiento_modalidad_jornada_id)');
        }

        if ($this->foraneaExiste('establecimiento_asignaturas', 'est_asig_asig_fk')) {
            DB::statement('alter table establecimiento_asignaturas drop foreign key est_asig_asig_fk');
        }

        if ($this->indiceExiste('establecimiento_asignaturas', 'est_asig_unique')) {
            DB::statement('alter table establecimiento_asignaturas drop index est_asig_unique');
        }

        if ($this->indiceExiste('establecimiento_asignaturas', 'est_asig_asig_fk')) {
            DB::statement('alter table establecimiento_asignaturas drop index est_asig_asig_fk');
        }
    }

    private function quitarIndiceTemporalOferta(): void
    {
        if ($this->indiceExiste('establecimiento_asignaturas', 'est_asig_oferta_idx')
            && $this->indiceExiste('establecimiento_asignaturas', 'est_asig_unique')) {
            DB::statement('alter table establecimiento_asignaturas drop index est_asig_oferta_idx');
        }
    }

    private function foraneaExiste(string $tabla, string $nombre): bool
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return false;
        }

        $filas = DB::select(
            'select 1 as presente from information_schema.TABLE_CONSTRAINTS where TABLE_SCHEMA = database() and TABLE_NAME = ? and CONSTRAINT_NAME = ? and CONSTRAINT_TYPE = ? limit 1',
            [$tabla, $nombre, 'FOREIGN KEY'],
        );

        return $filas !== [];
    }

    private function indiceExiste(string $tabla, string $indice): bool
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $filas = DB::select("select 1 as presente from sqlite_master where type = 'index' and tbl_name = ? and name = ? limit 1", [$tabla, $indice]);

            return $filas !== [];
        }

        $filas = DB::select(
            'select 1 as presente from information_schema.STATISTICS where TABLE_SCHEMA = database() and TABLE_NAME = ? and INDEX_NAME = ? limit 1',
            [$tabla, $indice],
        );

        return $filas !== [];
    }

    private function crearAsignaturasPropias(): void
    {
        Schema::create('establecimiento_asignaturas_propias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('establecimiento_id');
            $table->unsignedBigInteger('establecimiento_modalidad_jornada_id');
            $table->unsignedBigInteger('area_id');
            $table->string('nombre', 150);
            $table->string('tipo_asignatura', 20);
            $table->timestamps();

            $table->foreign('establecimiento_id', 'est_propia_est_fk')
                ->references('id')
                ->on('establecimientos')
                ->cascadeOnDelete();
            $table->foreign('establecimiento_modalidad_jornada_id', 'est_propia_oferta_fk')
                ->references('id')
                ->on('establecimiento_modalidad_jornadas')
                ->cascadeOnDelete();
            $table->foreign('area_id', 'est_propia_area_fk')
                ->references('id')
                ->on('sys_areas')
                ->restrictOnUpdate()
                ->restrictOnDelete();

            $table->unique(
                ['establecimiento_modalidad_jornada_id', 'area_id', 'nombre'],
                'est_propia_nombre_unique',
            );
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite' && ! $this->indiceExiste('establecimiento_asignaturas', 'est_asig_oferta_idx')) {
            DB::statement('alter table establecimiento_asignaturas add index est_asig_oferta_idx (establecimiento_modalidad_jornada_id)');
        }

        Schema::table('establecimiento_asignaturas', function (Blueprint $table) use ($driver): void {
            if ($driver === 'sqlite') {
                $table->dropForeign(['asignatura_propia_id']);
                $table->dropForeign(['asignatura_id']);
            } else {
                if ($this->foraneaExiste('establecimiento_asignaturas', 'est_asig_propia_fk')) {
                    $table->dropForeign('est_asig_propia_fk');
                }

                if ($this->foraneaExiste('establecimiento_asignaturas', 'est_asig_asig_fk')) {
                    $table->dropForeign('est_asig_asig_fk');
                }
            }

            if ($this->indiceExiste('establecimiento_asignaturas', 'est_asig_propia_unique')) {
                $table->dropUnique('est_asig_propia_unique');
            }

            if ($this->indiceExiste('establecimiento_asignaturas', 'est_asig_unique')) {
                $table->dropUnique('est_asig_unique');
            }

            $columnas = array_values(array_filter(
                ['asignatura_propia_id', 'tipo_asignatura'],
                fn (string $columna): bool => Schema::hasColumn('establecimiento_asignaturas', $columna),
            ));

            if ($columnas !== []) {
                $table->dropColumn($columnas);
            }
        });

        Schema::table('establecimiento_asignaturas', function (Blueprint $table): void {
            $table->unsignedBigInteger('asignatura_id')->nullable(false)->change();

            if (! $this->foraneaExiste('establecimiento_asignaturas', 'est_asig_asig_fk')) {
                $table->foreign('asignatura_id', 'est_asig_asig_fk')
                    ->references('id')
                    ->on('sys_asignaturas')
                    ->restrictOnUpdate()
                    ->restrictOnDelete();
            }

            if (! $this->indiceExiste('establecimiento_asignaturas', 'est_asig_unique')) {
                $table->unique(
                    ['establecimiento_modalidad_jornada_id', 'grado_id', 'asignatura_id'],
                    'est_asig_unique',
                );
            }
        });

        if ($driver !== 'sqlite') {
            $this->quitarIndiceTemporalOferta();
        }

        Schema::dropIfExists('establecimiento_asignaturas_propias');
    }
};
