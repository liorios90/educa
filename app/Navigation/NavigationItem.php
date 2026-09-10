<?php

namespace App\Navigation;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class NavigationItem
{
    /**
     * @param  list<Role>  $roles
     */
    public function __construct(
        public string $label,
        public string $route,
        public string $icon,
        public array $roles = [],
    ) {}

    public function visibleTo(User $user): bool
    {
        if ($this->roles === []) {
            return true;
        }

        return $user->hasAnyRole($this->roles);
    }

    public function isActive(): bool
    {
        return Request::routeIs($this->route);
    }

    public function url(): string
    {
        return route($this->route);
    }
}
