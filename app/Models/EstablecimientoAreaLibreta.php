<?php

namespace App\Models;

use App\Enums\ModoLibretaArea;
use Database\Factories\EstablecimientoAreaLibretaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstablecimientoAreaLibreta extends Model
{
    /** @use HasFactory<EstablecimientoAreaLibretaFactory> */
    use HasFactory;

    protected $table = 'establecimiento_area_libretas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'establecimiento_id',
        'establecimiento_modalidad_jornada_id',
        'grado_id',
        'area_id',
        'modo_libreta',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'modo_libreta' => ModoLibretaArea::Asignaturas->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'modo_libreta' => ModoLibretaArea::class,
        ];
    }

    protected static function newFactory(): EstablecimientoAreaLibretaFactory
    {
        return EstablecimientoAreaLibretaFactory::new();
    }

    /**
     * @return BelongsTo<Establecimiento, $this>
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    /**
     * @return BelongsTo<EstablecimientoModalidadJornada, $this>
     */
    public function oferta(): BelongsTo
    {
        return $this->belongsTo(EstablecimientoModalidadJornada::class, 'establecimiento_modalidad_jornada_id');
    }

    /**
     * @return BelongsTo<Sys_Grado, $this>
     */
    public function grado(): BelongsTo
    {
        return $this->belongsTo(Sys_Grado::class, 'grado_id');
    }

    /**
     * @return BelongsTo<Sys_Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Sys_Area::class, 'area_id');
    }
}
