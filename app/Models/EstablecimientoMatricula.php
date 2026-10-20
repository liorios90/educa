<?php

namespace App\Models;

use Database\Factories\EstablecimientoMatriculaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoMatricula extends Model
{
    /** @use HasFactory<EstablecimientoMatriculaFactory> */
    use HasFactory;

    protected $table = 'establecimiento_matriculas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'establecimiento_periodo_id',
        'establecimiento_aula_id',
        'alumno_id',
        'figura_profesional_id',
        'numero',
        'secuencia',
        'fecha',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'secuencia' => 'integer',
            'fecha' => 'date',
        ];
    }

    protected static function newFactory(): EstablecimientoMatriculaFactory
    {
        return EstablecimientoMatriculaFactory::new();
    }

    /**
     * @return BelongsTo<Establecimiento, $this>
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    /**
     * @return BelongsTo<EstablecimientoPeriodo, $this>
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoPeriodo::class, 'establecimiento_periodo_id');
    }

    /**
     * @return BelongsTo<EstablecimientoAula, $this>
     */
    public function aula(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoAula::class, 'establecimiento_aula_id');
    }

    /**
     * @return BelongsTo<Alumno, $this>
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    /**
     * @return BelongsTo<EstablecimientoFiguraProfesional, $this>
     */
    public function figuraProfesional(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoFiguraProfesional::class, 'figura_profesional_id');
    }
}
