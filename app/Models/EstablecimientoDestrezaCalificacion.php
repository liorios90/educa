<?php

namespace App\Models;

use App\Enums\CalificacionDestreza;
use Database\Factories\EstablecimientoDestrezaCalificacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoDestrezaCalificacion extends Model
{
    /** @use HasFactory<EstablecimientoDestrezaCalificacionFactory> */
    use HasFactory;

    protected $table = 'establecimiento_destreza_calificaciones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'alumno_id',
        'establecimiento_aula_id',
        'establecimiento_asignatura_id',
        'establecimiento_periodo_ciclo_id',
        'destreza_id',
        'calificacion',
        'observacion',
    ];

    protected static function newFactory(): EstablecimientoDestrezaCalificacionFactory
    {
        return EstablecimientoDestrezaCalificacionFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'calificacion' => CalificacionDestreza::class,
        ];
    }

    /**
     * @return BelongsTo<Alumno, $this>
     */
    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    /**
     * @return BelongsTo<Sys_Destreza, $this>
     */
    public function destreza(): BelongsTo
    {
        return $this->belongsTo(Sys_Destreza::class, 'destreza_id');
    }
}
