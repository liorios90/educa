<?php

namespace App\Http\Requests;

use App\Enums\ModoDecimal;
use App\Enums\Role;
use App\Models\Establecimiento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstablecimientoDecimalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasRole(Role::Admin)
            && $this->establecimiento() !== null;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'decimales_calificacion' => ['required', 'integer', 'min:0', 'max:4'],
            'modo_decimal' => ['required', Rule::enum(ModoDecimal::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'decimales_calificacion.required' => 'Indica con cuántos decimales trabaja el instituto.',
            'decimales_calificacion.integer' => 'La cantidad de decimales debe ser un número entero.',
            'decimales_calificacion.min' => 'La cantidad de decimales no puede ser menor que cero.',
            'decimales_calificacion.max' => 'La cantidad de decimales no puede ser mayor que 4.',
            'modo_decimal.required' => 'Indica si se trunca o se aproxima.',
            'modo_decimal.enum' => 'Selecciona si se trunca o se aproxima al inmediato superior.',
        ];
    }

    public function establecimiento(): ?Establecimiento
    {
        $establecimientoId = $this->user()?->establecimiento_id;

        if ($establecimientoId === null) {
            return null;
        }

        return Establecimiento::query()->find($establecimientoId);
    }
}
