<?php

namespace App\Models;

use Database\Factories\PersonaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Persona extends Model
{
    /** @use HasFactory<PersonaFactory> */
    use HasFactory;

    protected $table = 'personas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'tipo_identificacion_id',
        'identificacion',
        'nombres',
        'apellidos',
        'genero_id',
        'fecha_nacimiento',
        'ciudad_nacimiento',
        'provincia_id',
        'parroquia',
        'direccion',
        'telefono1',
        'telefono2',
        'nacionalidad_id',
        'usuario',
        'activo',
        'establecimiento_id',
        'lote',
    ];

    protected static function newFactory(): PersonaFactory
    {
        return PersonaFactory::new();
    }

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Sys_Provincia::class, 'provincia_id');
    }

    public function nacionalidad(): BelongsTo
    {
        return $this->belongsTo(Sys_Pais::class, 'nacionalidad_id');
    }

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Padre, $this>
     */
    public function padres(): HasMany
    {
        return $this->hasMany(Padre::class);
    }
}
