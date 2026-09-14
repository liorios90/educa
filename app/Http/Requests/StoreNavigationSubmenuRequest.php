<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\NavigationItem;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class StoreNavigationSubmenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        $parent = $this->route('navigationItem');

        return ($this->user()?->hasRole(Role::Sistemas) ?? false)
            && $parent instanceof NavigationItem
            && $parent->is_group
            && $parent->parent_id === null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'route_name' => ['required', 'string', 'max:255', $this->existingRoute()],
            'icon' => ['required', 'string', Rule::in(NavigationItem::ICONS)],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    private function existingRoute(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! Route::has((string) $value)) {
                $fail('La ruta indicada no existe.');
            }
        };
    }
}
