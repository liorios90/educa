<?php

namespace App\Http\Requests\Auth;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Auth\ActiveRole;
use App\Enums\Role;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SelectActiveOfertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && app(ActiveRole::class)->get($user) === Role::Admin
            && $user->establecimiento_id !== null;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'establecimiento_modalidad_id' => ['required', 'integer'],
            'oferta' => ['required', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'establecimiento_modalidad_id.required' => 'Selecciona la modalidad.',
            'oferta.required' => 'Selecciona la jornada.',
        ];
    }

    /**
     * @return list<\Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['establecimiento_modalidad_id', 'oferta'])) {
                    return;
                }

                $user = $this->user();

                if (! $user instanceof User) {
                    return;
                }

                $oferta = app(ActiveOferta::class)->available($user)
                    ->firstWhere('id', $this->integer('oferta'));

                if (
                    $oferta === null
                    || $oferta->establecimiento_modalidad_id !== $this->integer('establecimiento_modalidad_id')
                ) {
                    $validator->errors()->add(
                        'oferta',
                        'La modalidad y la jornada no están configuradas en tu establecimiento.',
                    );

                    return;
                }

                if (app(ActivePeriodo::class)->forOferta($oferta) === null) {
                    $validator->errors()->add(
                        'periodo',
                        'No existe ningún periodo activo.',
                    );
                }
            },
        ];
    }

    public function oferta(): EstablecimientoModalidadJornada
    {
        /** @var User $user */
        $user = $this->user();

        /** @var EstablecimientoModalidadJornada $oferta */
        $oferta = app(ActiveOferta::class)->available($user)
            ->firstWhere('id', $this->integer('oferta'));

        return $oferta;
    }

    public function periodo(): EstablecimientoPeriodo
    {
        /** @var EstablecimientoPeriodo $periodo */
        $periodo = app(ActivePeriodo::class)->forOferta($this->oferta());

        return $periodo;
    }
}
