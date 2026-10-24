<?php

namespace App\Http\Requests;

use App\Models\EstablecimientoCierreActividadCalificacion;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMejoraCalificacionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->hasRole('Docente');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'calificacion_id' => [
                'required',
                'integer',
                Rule::exists((new EstablecimientoCierreActividadCalificacion)->getTable(), 'id'),
            ],
            'nota_refuerzo' => ['nullable', 'numeric', 'between:0,10'],
            'nota_adicional' => ['required', 'numeric', 'between:0,10'],
            'fecha_notificacion' => ['required', 'date', 'before_or_equal:today'],
            'fecha_solicitud' => ['required', 'date', 'before_or_equal:today'],
            'fecha_evaluacion' => ['required', 'date', 'before_or_equal:today'],
            'referencia_solicitud' => ['required', 'string', 'max:255'],
        ];
    }
}
