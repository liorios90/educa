<?php

namespace App\Http\Requests;

use App\Enums\Role;
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
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'valor' => ['nullable', 'string', 'max:100'],
            'minimo' => ['nullable', 'numeric', 'decimal:0,2'],
            'maximo' => ['nullable', 'numeric', 'decimal:0,2', Rule::when($this->filled('minimo'), ['gte:minimo'])],
            'activo' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre del detalle.',
            'maximo.gte' => 'El mínimo no puede ser mayor que el máximo.',
        ];
    }

    /**
     * @return array{nombre: string, descripcion: ?string, valor: ?string, minimo: ?string, maximo: ?string, activo: bool}
     */
    public function detalle(): array
    {
        $data = $this->validated();

        return [
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'valor' => $data['valor'] ?? null,
            'minimo' => $data['minimo'] ?? null,
            'maximo' => $data['maximo'] ?? null,
            'activo' => (bool) $data['activo'],
        ];
    }
}
