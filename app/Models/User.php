<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'establecimiento_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsTo<Establecimiento, $this>
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    /**
     * @return HasOne<Persona, $this>
     */
    public function persona(): HasOne
    {
        return $this->hasOne(Persona::class);
    }

    public function nombreVisible(): string
    {
        $this->loadMissing('persona');
        $nombre = trim(($this->persona?->apellidos ?? '').' '.($this->persona?->nombres ?? ''));

        return $nombre !== '' ? $nombre : $this->name;
    }

    public function etiquetaRol(): string
    {
        $this->loadMissing('roles');

        $etiquetas = collect([
            Role::Docente,
            Role::Padre,
            Role::Alumno,
            Role::Admin,
            Role::Secretaria,
            Role::Sistemas,
            Role::Empleado,
        ])
            ->filter(fn (Role $rol): bool => $this->hasRole($rol))
            ->map(fn (Role $rol): string => $rol->label())
            ->implode(', ');

        return $etiquetas !== '' ? $etiquetas : 'Usuario';
    }

    public function etiquetaConRol(): string
    {
        return $this->nombreVisible().' ('.$this->etiquetaRol().')';
    }
}
