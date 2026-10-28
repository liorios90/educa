<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Equivalencia;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertEquivalenciaDetalleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasRole(Role::Admin);
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        $comportamiento = $this->equivalencia()->esComportamiento();

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => [$comportamiento ? 'required' : 'nullable', 'string'],
            'jerarquia' => [
                $comportamiento ? 'required' : 'prohibited',
                'integer',
                'between:1,99',
                Rule::unique('equivalencias_detalles', 'jerarquia')
                    ->where(fn ($query) => $query->where('equivalencia_id', $this->equivalencia()->id))
                    ->ignore($this->route('detalle')),
            ],
            'valor' => [
                $comportamiento ? 'required' : 'nullable',
                'string',
                'max:100',
                Rule::unique('equivalencias_detalles', 'valor')
                    ->where(fn ($query) => $query->where('equivalencia_id', $this->equivalencia()->id))
                    ->ignore($this->route('detalle')),
            ],
            'minimo' => [
                $comportamiento ? 'prohibited' : 'nullable',
                'numeric',
                'decimal:0,2',
            ],
            'maximo' => [
                $comportamiento ? 'prohibited' : 'nullable',
                'numeric',
                'decimal:0,2',
                Rule::when($this->filled('minimo'), ['gte:minimo']),
            ],
            'activo' => ['required', 'boolean'],
            'exige_observacion' => [$comportamiento ? 'required' : 'nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre del detalle.',
            'descripcion.required' => 'Escribe la descripción de la calificación.',
            'jerarquia.required' => 'Escribe el valor jerárquico. El más alto corresponde al mejor cumplimiento.',
            'jerarquia.unique' => 'Ese valor jerárquico ya está en la tabla.',
            'jerarquia.prohibited' => 'Solo la tabla de comportamiento usa un valor jerárquico.',
            'valor.required' => 'Escribe el código de la calificación.',
            'valor.unique' => 'Ese código ya está en la tabla.',
            'minimo.prohibited' => 'La tabla de comportamiento no usa rangos numéricos.',
            'maximo.prohibited' => 'La tabla de comportamiento no usa rangos numéricos.',
            'maximo.gte' => 'El mínimo no puede ser mayor que el máximo.',
        ];
    }

    /**
     * @return array{nombre: string, descripcion: ?string, valor: ?string, jerarquia: ?int, minimo: ?string, maximo: ?string, activo: bool, exige_observacion: bool}
     */
    public function detalle(): array
    {
        $data = $this->validated();

        return [
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'valor' => $data['valor'] ?? null,
            'jerarquia' => isset($data['jerarquia']) ? (int) $data['jerarquia'] : null,
            'minimo' => $data['minimo'] ?? null,
            'maximo' => $data['maximo'] ?? null,
            'activo' => (bool) $data['activo'],
            'exige_observacion' => (bool) ($data['exige_observacion'] ?? false),
        ];
    }

    private function equivalencia(): Equivalencia
    {
        $equivalencia = $this->route('equivalencia');

        if (! $equivalencia instanceof Equivalencia) {
            abort(404);
        }

        return $equivalencia;
    }
}
