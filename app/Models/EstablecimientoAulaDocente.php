<?php

namespace App\Models;

use Database\Factories\EstablecimientoAulaDocenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoAulaDocente extends Model
{
    /** @use HasFactory<EstablecimientoAulaDocenteFactory> */
    use HasFactory;

    protected $table = 'establecimiento_aula_docentes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_aula_id',
        'establecimiento_asignatura_id',
        'empleado_id',
    ];

    protected static function newFactory(): EstablecimientoAulaDocenteFactory
    {
        return EstablecimientoAulaDocenteFactory::new();
    }

    /**
     * @return BelongsTo<EstablecimientoAula, $this>
     */
    public function aula(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoAula::class, 'establecimiento_aula_id');
    }

    /**
     * @return BelongsTo<EstablecimientoAsignatura, $this>
     */
    public function malla(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoAsignatura::class, 'establecimiento_asignatura_id');
    }

    /**
     * @return BelongsTo<Empleado, $this>
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }
}
