<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class EstablecimientoPeriodoConfiguracionGrado extends Model
{
    protected $table = 'establecimiento_periodo_configuraciones_grado';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_periodo_id',
        'nombre',
        'porcentaje_examen_final',
        'porcentaje_proyecto_final',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'porcentaje_examen_final' => 'decimal:2',
            'porcentaje_proyecto_final' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<EstablecimientoPeriodo, $this>
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPeriodo::class, 'establecimiento_periodo_id');
    }

    /**
     * @return BelongsToMany<Sys_Grado, $this>
     */
    public function grados(): BelongsToMany
    {
        return $this->belongsToMany(Sys_Grado::class, 'establecimiento_periodo_configuracion_grado_grados', 'configuracion_grado_id', 'grado_id')
            ->withPivot('establecimiento_periodo_id');
    }

    /**
     * @return HasMany<EstablecimientoPeriodoConfiguracionGradoCiclo, $this>
     */
    public function ciclos(): HasMany
    {
        return $this->hasMany(EstablecimientoPeriodoConfiguracionGradoCiclo::class, 'configuracion_grado_id')->orderBy('ciclo_orden');
    }

    public static function eliminarAsignacionesHuerfanas(): int
    {
        return DB::table('establecimiento_periodo_configuracion_grado_grados')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('establecimiento_periodo_configuraciones_grado')
                    ->whereColumn(
                        'establecimiento_periodo_configuraciones_grado.id',
                        'establecimiento_periodo_configuracion_grado_grados.configuracion_grado_id',
                    );
            })
            ->delete();
    }

    /**
     * @return list<int>
     */
    public static function gradoIdsOcupados(int $periodoId, ?int $exceptoConfiguracionId = null): array
    {
        self::eliminarAsignacionesHuerfanas();

        return DB::table('establecimiento_periodo_configuracion_grado_grados')
            ->where('establecimiento_periodo_id', $periodoId)
            ->when(
                $exceptoConfiguracionId !== null,
                fn ($query) => $query->where('configuracion_grado_id', '!=', $exceptoConfiguracionId),
            )
            ->pluck('grado_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }
}
