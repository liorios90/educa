<?php

namespace App\Http\Requests;

use App\Auth\ActivePeriodo;
use App\Enums\Role;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AperturarEstablecimientoPeriodoRequest extends FormRequest
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

                if ($periodo === null) {
                    return;
                }

                if ($periodo->estaAbierto()) {
                    $validator->errors()->add(
                        'periodo',
                        'Este periodo ya fue aperturado. La configuración está bloqueada.',
                    );

                    return;
                }

                $periodo->loadMissing('ciclos');

                if ($periodo->ciclos->isEmpty() || $periodo->numero_parciales === null || $periodo->numero_parciales < 1) {
                    $validator->errors()->add(
                        'periodo',
                        'Guarda la configuración del periodo antes de aperturarlo.',
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
