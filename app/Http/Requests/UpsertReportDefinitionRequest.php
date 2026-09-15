<?php

namespace App\Http\Requests;

use App\Enums\ReportLayout;
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
            'layout' => ['required', Rule::enum(ReportLayout::class)],
            'table_border_width' => ['required', 'integer', 'min:0', 'max:5'],
            'table_border_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'table_header' => ['required', 'boolean'],
            'table_header_background' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'table_striped' => ['required', 'boolean'],
            'table_font_size' => ['required', 'integer', 'min:8', 'max:20'],
            'table_cell_padding' => ['required', 'integer', 'min:0', 'max:24'],
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
            'table_border_color.regex' => 'Usa un color en formato hexadecimal, por ejemplo #cbd5e1.',
            'table_header_background.regex' => 'Usa un color en formato hexadecimal, por ejemplo #f1f5f9.',
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

    /**
     * Table styling is optional in the payload so a report saved before the
     * table layout existed keeps working; absent keys fall back to the defaults.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'visible_to_all' => $this->boolean('visible_to_all'),
            'layout' => $this->input('layout', ReportLayout::Canvas->value),
            'table_border_width' => $this->input('table_border_width', 1),
            'table_border_color' => $this->input('table_border_color', '#cbd5e1'),
            'table_header' => $this->has('table_header') ? $this->boolean('table_header') : true,
            'table_header_background' => $this->input('table_header_background', '#f1f5f9'),
            'table_striped' => $this->boolean('table_striped'),
            'table_font_size' => $this->input('table_font_size', 12),
            'table_cell_padding' => $this->input('table_cell_padding', 8),
        ]);
    }
}
