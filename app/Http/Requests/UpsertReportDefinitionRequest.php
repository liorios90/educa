<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Reports\ReportCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpsertReportDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::Sistemas) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $sources = array_keys(config('reports.sources', []));

        return [
            'name' => ['required', 'string', 'max:255'],
            'source' => ['required', 'string', Rule::in($sources)],
            'is_active' => ['required', 'boolean'],
            'visible_to_all' => ['required', 'boolean'],
            'roles' => ['exclude_if:visible_to_all,true', 'required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.column' => ['required', 'string', 'max:64'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.label_x' => ['nullable', 'integer', 'min:0', 'max:90'],
            'fields.*.label_y' => ['nullable', 'integer', 'min:0', 'max:90'],
            'fields.*.value_x' => ['nullable', 'integer', 'min:0', 'max:90'],
            'fields.*.value_y' => ['nullable', 'integer', 'min:0', 'max:90'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roles.required' => 'Selecciona al menos un rol, o marca que sea visible para todos.',
            'fields.required' => 'Incluye al menos un campo en el reporte.',
            'fields.min' => 'Incluye al menos un campo en el reporte.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $catalog = $this->container->make(ReportCatalog::class);
                $source = (string) $this->input('source');

                foreach ($this->input('fields', []) as $index => $field) {
                    $column = $field['column'] ?? '';

                    if (! is_string($column) || ! $catalog->hasField($source, $column)) {
                        $validator->errors()->add(
                            "fields.{$index}.column",
                            'El campo no pertenece a la tabla o relación seleccionada.',
                        );
                    }
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'visible_to_all' => $this->boolean('visible_to_all'),
        ]);
    }
}
