<?php

namespace App\Http\Requests;

use App\Auth\ActiveOferta;
use App\Auth\ActivePeriodo;
use App\Enums\Role;
use App\Models\EstablecimientoAula;
use App\Models\EstablecimientoGrado;
use App\Models\EstablecimientoModalidadJornada;
use App\Models\EstablecimientoPeriodo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEstablecimientoAulaRequest extends FormRequest
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
            'establecimiento_grado_id' => [
                'required',
                'integer',
                Rule::exists('establecimiento_grados', 'id'),
            ],
            'paralelos' => ['required', 'array', 'min:1'],
            'paralelos.*' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'establecimiento_grado_id.required' => 'Selecciona el grado del aula.',
            'establecimiento_grado_id.exists' => 'El grado no pertenece a la modalidad y jornada activas.',
            'paralelos.required' => 'Indica al menos un paralelo.',
            'paralelos.min' => 'Indica al menos un paralelo.',
            'paralelos.*.regex' => 'El paralelo solo puede tener letras y números.',
            'paralelos.*.max' => 'Cada paralelo puede tener hasta 10 caracteres.',
            'paralelos.*.distinct' => 'Cada paralelo debe escribirse una sola vez.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->oferta() === null) {
                    $validator->errors()->add(
                        'establecimiento_grado_id',
                        'Selecciona primero la modalidad y la jornada.',
                    );

                    return;
                }

                if ($this->periodo() === null) {
                    $validator->errors()->add(
                        'establecimiento_grado_id',
                        'No existe ningún periodo activo.',
                    );

                    return;
                }

                if ($validator->errors()->has('establecimiento_grado_id')) {
                    return;
                }

                $grado = EstablecimientoGrado::query()->find($this->integer('establecimiento_grado_id'));
                $oferta = $this->oferta();
                $user = $this->user();

                if (
                    $grado === null
                    || $oferta === null
                    || $user === null
                    || (int) $grado->establecimiento_id !== (int) $user->establecimiento_id
                    || (int) $grado->establecimiento_modalidad_jornada_id !== (int) $oferta->id
                ) {
                    $validator->errors()->add(
                        'establecimiento_grado_id',
                        'El grado no pertenece a la modalidad y jornada activas.',
                    );

                    return;
                }

                if ($this->paralelosConError($validator)) {
                    return;
                }

                $periodo = $this->periodo();
                $paralelos = $this->paralelos();

                if ($periodo === null || $paralelos === []) {
                    return;
                }

                $existentes = EstablecimientoAula::query()
                    ->where('establecimiento_periodo_id', $periodo->id)
                    ->where('establecimiento_grado_id', $grado->id)
                    ->whereIn('paralelo', $paralelos)
                    ->orderBy('paralelo')
                    ->pluck('paralelo')
                    ->all();

                if ($existentes !== []) {
                    $validator->errors()->add(
                        'paralelos',
                        'Ya existe un aula con el paralelo '.implode(', ', $existentes).' en este grado y periodo.',
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->errorBag = 'aula-create';

        $this->merge([
            'paralelos' => $this->paralelosDesdeEntrada(),
        ]);
    }

    /**
     * @return list<string>
     */
    public function paralelos(): array
    {
        return array_values(array_filter(
            (array) $this->input('paralelos', []),
            fn (mixed $paralelo): bool => is_string($paralelo) && $paralelo !== '',
        ));
    }

    /**
     * @return list<string>
     */
    private function paralelosDesdeEntrada(): array
    {
        $entrada = $this->input('paralelos', '');
        $texto = is_array($entrada) ? implode(',', $entrada) : (string) $entrada;

        return collect(preg_split('/[\s,;]+/', $texto) ?: [])
            ->map(fn (string $paralelo): string => Str::upper(trim($paralelo)))
            ->filter(fn (string $paralelo): bool => $paralelo !== '')
            ->values()
            ->all();
    }

    private function paralelosConError(Validator $validator): bool
    {
        foreach ($validator->errors()->keys() as $key) {
            if ($key === 'paralelos' || str_starts_with($key, 'paralelos.')) {
                return true;
            }
        }

        return false;
    }

    public function oferta(): ?EstablecimientoModalidadJornada
    {
        return $this->container->make(ActiveOferta::class)->get($this->user());
    }

    public function periodo(): ?EstablecimientoPeriodo
    {
        return $this->container->make(ActivePeriodo::class)->get($this->user());
    }
}
