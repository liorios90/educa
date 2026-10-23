<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('establecimiento_periodo_configuracion_grado_grados')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('establecimiento_periodo_configuraciones_grado')
                    ->whereColumn(
                        'establecimiento_periodo_configuraciones_grado.id',
                        'establecimiento_periodo_configuracion_grado_grados.configuracion_grado_id',
                    );
            })
            ->delete();

        Schema::table('establecimiento_periodo_configuracion_grado_grados', function (Blueprint $table): void {
            if (! $this->tieneForeign('epcgg_config_fk')) {
                $table->foreign('configuracion_grado_id', 'epcgg_config_fk')
                    ->references('id')
                    ->on('establecimiento_periodo_configuraciones_grado')
                    ->cascadeOnDelete();
            }

            if (! $this->tieneForeign('epcgg_grado_fk')) {
                $table->foreign('grado_id', 'epcgg_grado_fk')
                    ->references('id')
                    ->on('sys_grados')
                    ->cascadeOnDelete();
            }

            if (! $this->tieneForeign('epcgg_periodo_fk')) {
                $table->foreign('establecimiento_periodo_id', 'epcgg_periodo_fk')
                    ->references('id')
                    ->on('establecimiento_periodos')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('establecimiento_periodo_configuracion_grado_grados', function (Blueprint $table): void {
            if ($this->tieneForeign('epcgg_periodo_fk')) {
                $table->dropForeign('epcgg_periodo_fk');
            }

            if ($this->tieneForeign('epcgg_grado_fk')) {
                $table->dropForeign('epcgg_grado_fk');
            }

            if ($this->tieneForeign('epcgg_config_fk')) {
                $table->dropForeign('epcgg_config_fk');
            }
        });
    }

    private function tieneForeign(string $nombre): bool
    {
        return collect(Schema::getForeignKeys('establecimiento_periodo_configuracion_grado_grados'))
            ->contains(fn (array $foreign): bool => ($foreign['name'] ?? '') === $nombre);
    }
};
