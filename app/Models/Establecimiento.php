<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\EstablecimientoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Establecimiento extends Model
{
    /** @use HasFactory<EstablecimientoFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'direccion',
        'telefono',
        'representante',
        'codigo_amie',
        'regimen',
        'email',
        'usuario',
        'activo',
        'logo',
        'only_visible',
        'mision',
        'vision',
        'ideario',
        'grupo_amie',
        'zona_id',
        'distrito_id',
        'circuito_id',
    ];

    protected static function newFactory(): EstablecimientoFactory
    {
        return EstablecimientoFactory::new();
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Sys_Zona::class, 'zona_id');
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Sys_Distrito::class, 'distrito_id');
    }

    public function circuito(): BelongsTo
    {
        return $this->belongsTo(Sys_Circuito::class, 'circuito_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function administrador(): ?User
    {
        return $this->users()
            ->whereHas('roles', fn ($query) => $query->where('name', Role::Admin->value))
            ->orderBy('id')
            ->first();
    }

    public function logoUrl(): ?string
    {
        if (! is_string($this->logo) || $this->logo === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($this->logo)) {
            return null;
        }

        return Storage::disk('public')->url($this->logo);
    }

    public function monograma(): string
    {
        $parts = preg_split('/\s+/u', trim($this->nombre)) ?: [];
        $letters = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $letters !== '' ? $letters : 'E';
    }
}
