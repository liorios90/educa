<?php

namespace App\Http\Requests;

use App\Auth\ActivePeriodo;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarCierresParcialesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->periodo()?->estaAbierto() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $parcialesPermitidos = $this->periodo()?->ciclos()
            ->with('parciales')
            ->get()
            ->flatMap(fn ($ciclo) => $ciclo->parciales)
            ->pluck('id')
            ->all() ?? [];

        return [
            'parciales_cerrados' => ['nullable', 'array'],
            'parciales_cerrados.*' => ['required', 'integer', 'distinct', Rule::in($parcialesPermitidos)],
        ];
    }

    public function periodo(): ?EstablecimientoPeriodo
    {
        return $this->container->make(ActivePeriodo::class)->get($this->user());
    }
}
