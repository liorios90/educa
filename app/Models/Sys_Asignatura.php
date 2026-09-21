<?php

namespace App\Models;

use Database\Factories\SysAsignaturaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sys_Asignatura extends Model
{
    /** @use HasFactory<SysAsignaturaFactory> */
    use HasFactory;

    protected $table = 'sys_asignaturas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'orden',
        'horas_semanales',
        'aparece_en_libreta',
        'area_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'orden' => 0,
        'aparece_en_libreta' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'horas_semanales' => 'integer',
            'aparece_en_libreta' => 'boolean',
        ];
    }

    protected static function newFactory(): SysAsignaturaFactory
    {
        return SysAsignaturaFactory::new();
    }

    /**
     * @return BelongsTo<Sys_Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Sys_Area::class, 'area_id');
    }
}
