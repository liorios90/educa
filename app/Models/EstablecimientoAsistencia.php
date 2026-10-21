<?php

namespace App\Models;

use App\Enums\EstadoAsistencia;
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
        'establecimiento_aula_id',
        'fecha',
        'estado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => EstadoAsistencia::class,
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
}
