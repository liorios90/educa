<?php

namespace App\Models;

use Database\Factories\AlumnoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alumno extends Model
{
    /** @use HasFactory<AlumnoFactory> */
    use HasFactory;

    protected $table = 'alumnos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'persona_id',
        'padre_id',
        'contacto_emergencia',
        'usuario',
        'id_estructura_form_matricula',
        'activo',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'id_estructura_form_matricula' => 0,
    ];

    protected static function newFactory(): AlumnoFactory
    {
        return AlumnoFactory::new();
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(Padre::class);
    }
}
