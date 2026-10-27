<?php

namespace App\Models;

use App\Enums\EstadoAsistencia;
use App\Enums\ModoAsistencia;
use App\Enums\MotivoJustificacionAsistencia;
use Database\Factories\EstablecimientoAsistenciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoAsistencia extends Model
{
    /** @use HasFactory<EstablecimientoAsistenciaFactory> */
    use HasFactory;

    protected $table = 'establecimiento_asistencias';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'alumno_id',
        'establecimiento_asignatura_id',
        'clave_asignatura',
        'establecimiento_aula_id',
        'establecimiento_periodo_ciclo_id',
        'parcial_numero',
        'fecha',
        'alcance',
        'estado',
        'justificacion',
    ];

    protected static function booted(): void
    {
        static::saving(function (EstablecimientoAsistencia $asistencia): void {
            $asistencia->clave_asignatura = (int) ($asistencia->establecimiento_asignatura_id ?? 0);

            if (! $asistencia->alcance instanceof ModoAsistencia) {
                $asistencia->alcance = $asistencia->clave_asignatura === 0
                    ? ModoAsistencia::Jornada
                    : ModoAsistencia::Asignatura;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'clave_asignatura' => 'integer',
            'parcial_numero' => 'integer',
            'alcance' => ModoAsistencia::class,
            'estado' => EstadoAsistencia::class,
            'justificacion' => MotivoJustificacionAsistencia::class,
        ];
    }

    protected static function newFactory(): EstablecimientoAsistenciaFactory
    {
        return EstablecimientoAsistenciaFactory::new();
    }

    /**
     * @return BelongsTo<Establecimiento, $this>
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    /**
     * @return BelongsTo<Alumno, $this>
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    /**
     * @return BelongsTo<EstablecimientoAsignatura, $this>
     */
    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoAsignatura::class, 'establecimiento_asignatura_id');
    }

    /**
     * @return BelongsTo<EstablecimientoAula, $this>
     */
    public function aula(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoAula::class, 'establecimiento_aula_id');
    }

    /**
     * @return BelongsTo<EstablecimientoPeriodoCiclo, $this>
     */
    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPeriodoCiclo::class, 'establecimiento_periodo_ciclo_id');
    }
}
