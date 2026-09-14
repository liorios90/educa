<?php

namespace App\Navigation;

use App\Enums\Role;
use App\Models\NavigationItem as NavigationItemModel;
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
        public ?int $groupId = null,
    ) {}

    public function visibleTo(User $user): bool
    {
        if ($this->roles === []) {
            return true;
        }

        return $user->hasAnyRole($this->roles);
    }

    public function isGroup(): bool
    {
        return $this->groupId !== null;
    }

    public function isActive(): bool
    {
        if ($this->isGroup()) {
            $current = Request::route('navigationItem');
            $currentId = $current instanceof NavigationItemModel
                ? $current->getKey()
                : (int) $current;

            return Request::routeIs('navigation.hub') && $currentId === $this->groupId;
        }

        return Request::routeIs($this->route);
    }

    public function url(): string
    {
        if ($this->isGroup()) {
            return route('navigation.hub', $this->groupId);
        }

        return route($this->route);
    }
}
