<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Sys_Grado;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SyncEstablecimientoEstructuraRequest extends FormRequest
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
            'grados' => ['nullable', 'array'],
            'grados.*' => ['integer', 'distinct', Rule::exists('sys_grados', 'id')],
            'nombre_grados' => ['nullable', 'array'],
            'nombre_grados.*' => ['nullable', 'string', 'max:150'],
            'nombre_subniveles' => ['nullable', 'array'],
            'nombre_subniveles.*' => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['grados', 'grados.*'])) {
                    return;
                }

                $gradoIds = $this->intIdsFromInput('grados');
                $grados = Sys_Grado::query()
                    ->with('subnivel')
                    ->whereIn('id', $gradoIds)
                    ->get();

                foreach ($grados as $grado) {
                    if ($grado->subnivel_id === null || $grado->subnivel?->nivel_id === null) {
                        $validator->errors()->add('grados', 'Cada grado debe pertenecer a un subnivel y un nivel del catálogo.');
                        break;
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'grados.*.exists' => 'El grado seleccionado no existe en el catálogo de sistemas.',
            'nombre_grados.*.max' => 'El nombre del grado en el establecimiento no puede tener más de 150 caracteres.',
            'nombre_subniveles.*.max' => 'El nombre del subnivel en el establecimiento no puede tener más de 150 caracteres.',
        ];
    }

    /**
     * @return list<int>
     */
    public function gradoIds(): array
    {
        return $this->intIds('grados');
    }

    /**
     * @return array<int, string|null>
     */
    public function nombresGrados(): array
    {
        return $this->nombresForIds($this->gradoIds(), 'nombre_grados');
    }

    /**
     * @return array<int, string|null>
     */
    public function nombresSubniveles(): array
    {
        $subnivelIds = Sys_Grado::query()
            ->whereIn('id', $this->gradoIds())
            ->whereNotNull('subnivel_id')
            ->pluck('subnivel_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $this->nombresForIds($subnivelIds, 'nombre_subniveles');
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, string|null>
     */
    private function nombresForIds(array $ids, string $key): array
    {
        /** @var array<int|string, mixed> $raw */
        $raw = $this->validated($key) ?? [];

        $nombres = [];

        foreach ($ids as $id) {
            $nombres[$id] = $this->trimmedNombre($raw[$id] ?? $raw[(string) $id] ?? null);
        }

        return $nombres;
    }

    private function trimmedNombre(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $nombre = Str::of((string) $value)->trim()->toString();

        return $nombre === '' ? null : $nombre;
    }

    /**
     * @return list<int>
     */
    private function intIds(string $key): array
    {
        /** @var list<int|string>|null $values */
        $values = $this->validated($key);

        return array_values(array_unique(array_map(intval(...), $values ?? [])));
    }

    /**
     * @return list<int>
     */
    private function intIdsFromInput(string $key): array
    {
        /** @var list<int|string> $values */
        $values = $this->input($key, []);

        return array_values(array_unique(array_map(intval(...), $values)));
    }
}
