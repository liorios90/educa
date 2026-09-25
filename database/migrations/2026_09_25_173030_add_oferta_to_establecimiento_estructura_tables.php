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
        $this->addOfertaColumn('establecimiento_niveles');
        $this->addOfertaColumn('establecimiento_subniveles');
        $this->addOfertaColumn('establecimiento_grados');

        $this->dropCatalogUnique('establecimiento_niveles', ['establecimiento_id', 'nivel_id'], 'est_niv_est_idx');
        $this->dropCatalogUnique('establecimiento_subniveles', ['establecimiento_id', 'subnivel_id'], 'est_sub_est_idx');
        $this->dropCatalogUnique('establecimiento_grados', ['establecimiento_id', 'grado_id'], 'est_gra_est_idx');

        $this->backfillOfertas('establecimiento_niveles');
        $this->backfillOfertas('establecimiento_subniveles');
        $this->backfillOfertas('establecimiento_grados');

        $this->constrainOferta('establecimiento_niveles', 'nivel_id', 'est_niv_oferta_fk', 'est_niv_oferta_unique');
        $this->constrainOferta('establecimiento_subniveles', 'subnivel_id', 'est_sub_oferta_fk', 'est_sub_oferta_unique');
        $this->constrainOferta('establecimiento_grados', 'grado_id', 'est_gra_oferta_fk', 'est_gra_oferta_unique');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropOfertaConstraint('establecimiento_grados', 'est_gra_oferta_fk', 'est_gra_oferta_unique');
        $this->dropOfertaConstraint('establecimiento_subniveles', 'est_sub_oferta_fk', 'est_sub_oferta_unique');
        $this->dropOfertaConstraint('establecimiento_niveles', 'est_niv_oferta_fk', 'est_niv_oferta_unique');

        Schema::table('establecimiento_niveles', function (Blueprint $table) {
            $table->dropColumn('establecimiento_modalidad_jornada_id');
            $table->unique(['establecimiento_id', 'nivel_id']);
        });
        Schema::table('establecimiento_subniveles', function (Blueprint $table) {
            $table->dropColumn('establecimiento_modalidad_jornada_id');
            $table->unique(['establecimiento_id', 'subnivel_id']);
        });
        Schema::table('establecimiento_grados', function (Blueprint $table) {
            $table->dropColumn('establecimiento_modalidad_jornada_id');
            $table->unique(['establecimiento_id', 'grado_id']);
        });
    }

    private function addOfertaColumn(string $table): void
    {
        if (Schema::hasColumn($table, 'establecimiento_modalidad_jornada_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->unsignedBigInteger('establecimiento_modalidad_jornada_id')
                ->nullable()
                ->after('establecimiento_id');
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropCatalogUnique(string $table, array $columns, string $establecimientoIndex): void
    {
        if (! $this->hasIndex($table, $establecimientoIndex)) {
            Schema::table($table, function (Blueprint $blueprint) use ($establecimientoIndex): void {
                $blueprint->index('establecimiento_id', $establecimientoIndex);
            });
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
            $blueprint->dropUnique($columns);
        });
    }

    private function hasIndex(string $table, string $name): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['name'] ?? '') === $name) {
                return true;
            }
        }

        return false;
    }

    private function backfillOfertas(string $table): void
    {
        $ofertasPorEstablecimiento = DB::table('establecimiento_modalidad_jornadas as omj')
            ->join('establecimiento_modalidades as em', 'em.id', '=', 'omj.establecimiento_modalidad_id')
            ->select('omj.id', 'em.establecimiento_id')
            ->get()
            ->groupBy('establecimiento_id');

        $pendientes = DB::table($table)
            ->whereNull('establecimiento_modalidad_jornada_id')
            ->get();

        foreach ($pendientes as $row) {
            $ofertas = $ofertasPorEstablecimiento->get($row->establecimiento_id, collect());

            if ($ofertas->isEmpty()) {
                continue;
            }

            foreach ($ofertas as $oferta) {
                $copy = (array) $row;
                unset($copy['id']);
                $copy['establecimiento_modalidad_jornada_id'] = $oferta->id;
                DB::table($table)->insert($copy);
            }

            DB::table($table)->where('id', $row->id)->delete();
        }
    }

    private function constrainOferta(string $table, string $catalogColumn, string $foreignName, string $uniqueName): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($catalogColumn, $foreignName, $uniqueName): void {
            $blueprint->foreign('establecimiento_modalidad_jornada_id', $foreignName)
                ->references('id')
                ->on('establecimiento_modalidad_jornadas')
                ->cascadeOnDelete();
            $blueprint->unique(['establecimiento_modalidad_jornada_id', $catalogColumn], $uniqueName);
        });
    }

    private function dropOfertaConstraint(string $table, string $foreignName, string $uniqueName): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($foreignName, $uniqueName): void {
            $blueprint->dropForeign($foreignName);
            $blueprint->dropUnique($uniqueName);
        });
    }
};
