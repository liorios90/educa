<?php

namespace App\Http\Requests;

use App\Auth\ActivePeriodo;
use App\Enums\Role;
use App\Enums\UsoEquivalencia;
use App\Models\Equivalencia;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AsignarEquivalenciaAsignaturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasRole(Role::Admin)
            && $user->establecimiento_id !== null;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'asignatura_id' => ['required', 'integer'],
            'grado_id' => ['required', 'integer'],
            'equivalencia_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || ! $this->filled('equivalencia_id')) {
                    return;
                }

                if ($this->tablaEquivalencia() === null) {
                    $validator->errors()->add(
                        'equivalencia_id',
                        'Elige una tabla de equivalencia activa de este periodo.',
                    );
                }
            },
        ];
    }

    public function tablaEquivalencia(): ?Equivalencia
    {
        if (! $this->filled('equivalencia_id')) {
            return null;
        }

        $periodo = $this->container->make(ActivePeriodo::class)->get($this->user());

        if ($periodo === null) {
            return null;
        }

        return Equivalencia::query()
            ->whereKey($this->integer('equivalencia_id'))
            ->where('establecimiento_periodo_id', $periodo->id)
            ->where('uso', UsoEquivalencia::Asignatura)
            ->where('activo', true)
            ->first();
    }
}
