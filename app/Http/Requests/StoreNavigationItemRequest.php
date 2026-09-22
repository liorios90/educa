<?php

namespace App\Http\Requests;

use App\Enums\NavigationGroupDisplay;
use App\Enums\Role;
use App\Models\NavigationItem;
use App\Navigation\NavigationIcons;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class StoreNavigationItemRequest extends FormRequest
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
        $item = $this->route('navigationItem');
        $hasChildren = $item instanceof NavigationItem && $item->children()->exists();

        return [
            'label' => ['required', 'string', 'max:255'],
            'is_group' => ['required', 'boolean', Rule::when($hasChildren, ['accepted'])],
            'group_display' => [
                Rule::requiredIf($this->boolean('is_group')),
                'nullable',
                Rule::enum(NavigationGroupDisplay::class),
            ],
            'route_name' => [
                Rule::requiredIf(! $this->boolean('is_group')),
                'nullable',
                'string',
                'max:255',
                $this->existingRoute(),
            ],
            'icon' => ['required', 'string', Rule::in(NavigationIcons::names())],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'visible_to_all' => ['required', 'boolean'],
            'roles' => ['exclude_if:visible_to_all,true', 'required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roles.required' => 'Selecciona al menos un rol, o marca que sea visible para todos.',
            'is_group.accepted' => 'No puedes quitar el grupo mientras tenga submenús.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $isGroup = $this->boolean('is_group');

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'visible_to_all' => $this->boolean('visible_to_all'),
            'is_group' => $isGroup,
            'group_display' => $isGroup
                ? ($this->input('group_display') ?: NavigationGroupDisplay::Screen->value)
                : NavigationGroupDisplay::Screen->value,
        ]);
    }

    private function existingRoute(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->boolean('is_group') || $value === null || $value === '') {
                return;
            }

            if (! Route::has((string) $value)) {
                $fail('La ruta indicada no existe.');
            }
        };
    }
}
