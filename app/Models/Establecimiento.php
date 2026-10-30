<?php

namespace App\Models;

use App\Enums\AsistenteProveedor;
use App\Enums\ModoDecimal;
use App\Enums\Role;
use Database\Factories\EstablecimientoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'bachillerato_tecnico',
        'bachillerato_internacional',
        'logo',
        'only_visible',
        'mision',
        'vision',
        'ideario',
        'decimales_calificacion',
        'modo_decimal',
        'grupo_amie',
        'zona_id',
        'distrito_id',
        'circuito_id',
        'gemini_api_key',
        'gemini_model',
        'asistente_proveedor',
        'asistente_base_url',
        'gemini_preguntas_limite',
        'gemini_tokens_limite',
        'gemini_preguntas_usadas',
        'gemini_tokens_usados',
        'gemini_uso_mes',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'gemini_api_key',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'decimales_calificacion' => 2,
        'modo_decimal' => 'aproximar',
        'asistente_proveedor' => 'gemini',
    ];

    protected static function newFactory(): EstablecimientoFactory
    {
        return EstablecimientoFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'bachillerato_tecnico' => 'boolean',
            'bachillerato_internacional' => 'boolean',
            'gemini_api_key' => 'encrypted',
            'asistente_proveedor' => AsistenteProveedor::class,
            'gemini_preguntas_limite' => 'integer',
            'gemini_tokens_limite' => 'integer',
            'gemini_preguntas_usadas' => 'integer',
            'gemini_tokens_usados' => 'integer',
            'decimales_calificacion' => 'integer',
            'modo_decimal' => ModoDecimal::class,
        ];
    }

    public function tieneCredencialesGemini(): bool
    {
        return is_string($this->gemini_api_key) && $this->gemini_api_key !== '';
    }

    public function proveedorAsistente(): AsistenteProveedor
    {
        return $this->asistente_proveedor ?? AsistenteProveedor::Gemini;
    }

    public function tieneCredencialesAsistente(): bool
    {
        if (! $this->tieneCredencialesGemini()) {
            return false;
        }

        if ($this->proveedorAsistente() === AsistenteProveedor::Compatible) {
            return is_string($this->asistente_base_url) && $this->asistente_base_url !== '';
        }

        return true;
    }

    public function modeloGemini(): string
    {
        if (is_string($this->gemini_model) && $this->gemini_model !== '') {
            return $this->gemini_model;
        }

        return (string) config('services.gemini.model');
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
     * @return HasMany<EstablecimientoModalidad, $this>
     */
    public function establecimientoModalidades(): HasMany
    {
        return $this->hasMany(EstablecimientoModalidad::class);
    }

    /**
     * @return BelongsToMany<Sys_Modalidad, $this>
     */
    public function modalidades(): BelongsToMany
    {
        return $this->belongsToMany(Sys_Modalidad::class, 'establecimiento_modalidades', 'establecimiento_id', 'modalidad_id')
            ->withTimestamps();
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<EstablecimientoPeriodo, $this>
     */
    public function periodos(): HasMany
    {
        return $this->hasMany(EstablecimientoPeriodo::class);
    }

    /**
     * @return HasMany<EstablecimientoNivel, $this>
     */
    public function establecimientoNiveles(): HasMany
    {
        return $this->hasMany(EstablecimientoNivel::class);
    }

    /**
     * @return HasMany<EstablecimientoSubnivel, $this>
     */
    public function establecimientoSubniveles(): HasMany
    {
        return $this->hasMany(EstablecimientoSubnivel::class);
    }

    /**
     * @return HasMany<EstablecimientoGrado, $this>
     */
    public function establecimientoGrados(): HasMany
    {
        return $this->hasMany(EstablecimientoGrado::class);
    }

    /**
     * @return HasMany<EstablecimientoAula, $this>
     */
    public function aulas(): HasMany
    {
        return $this->hasMany(EstablecimientoAula::class);
    }

    /** @return HasMany<EstablecimientoFiguraProfesional, $this> */
    public function figurasProfesionales(): HasMany
    {
        return $this->hasMany(EstablecimientoFiguraProfesional::class);
    }

    /**
     * @return BelongsToMany<Sys_Nivel, $this>
     */
    public function niveles(): BelongsToMany
    {
        return $this->belongsToMany(Sys_Nivel::class, 'establecimiento_niveles', 'establecimiento_id', 'nivel_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Sys_Subnivel, $this>
     */
    public function subniveles(): BelongsToMany
    {
        return $this->belongsToMany(Sys_Subnivel::class, 'establecimiento_subniveles', 'establecimiento_id', 'subnivel_id')
            ->withPivot('nivel_id', 'nombre')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Sys_Grado, $this>
     */
    public function grados(): BelongsToMany
    {
        return $this->belongsToMany(Sys_Grado::class, 'establecimiento_grados', 'establecimiento_id', 'grado_id')
            ->withPivot('subnivel_id', 'nombre')
            ->withTimestamps();
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
