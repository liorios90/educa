<?php

namespace App\Http\Requests;

use App\Auth\ActivePeriodo;
use App\Enums\Role;
use App\Models\EstablecimientoPeriodo;
use App\Services\RevertirAperturaPeriodo;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class RevertirAperturaEstablecimientoPeriodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasRole(Role::Admin)
            && $user->establecimiento_id !== null
            && $this->periodo() !== null;
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
        return $this->container->make(ActivePeriodo::class)->get($this->user());
    }
}
