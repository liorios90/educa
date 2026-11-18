<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\EstablecimientoPeriodo;
use App\Services\RevertirAperturaPeriodo;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class RevertirAperturaPeriodoSistemasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::Sistemas) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $periodo = $this->periodo();

                if ($periodo === null || $validator->errors()->isNotEmpty()) {
                    return;
                }

                if (! $periodo->estaAbierto()) {
                    $validator->errors()->add(
                        'periodo',
                        RevertirAperturaPeriodo::MENSAJE_SIN_APERTURA,
                    );

                    return;
                }

                if ($this->container->make(RevertirAperturaPeriodo::class)->tieneCalificaciones($periodo)) {
                    $validator->errors()->add(
                        'periodo',
                        RevertirAperturaPeriodo::MENSAJE_CON_CALIFICACIONES,
                    );
                }
            },
        ];
    }

    public function periodo(): ?EstablecimientoPeriodo
    {
        $periodo = $this->route('periodo');

        return $periodo instanceof EstablecimientoPeriodo ? $periodo : null;
    }
}
