<?php

namespace App\Navigation;

use App\Enums\NavigationGroupDisplay;
use App\Enums\Role;
use App\Models\NavigationItem as NavigationItemModel;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class NavigationItem
{
    /**
     * @param  list<Role>  $roles
     * @param  list<self>  $children
     */
    public function __construct(
        public string $label,
        public string $route,
        public string $icon,
        public array $roles = [],
        public ?int $groupId = null,
        public NavigationGroupDisplay $groupDisplay = NavigationGroupDisplay::Screen,
        public array $children = [],
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

    public function showsChildrenInSidebar(): bool
    {
        return $this->isGroup() && $this->groupDisplay === NavigationGroupDisplay::Sidebar;
    }

    public function isActive(): bool
    {
        if ($this->showsChildrenInSidebar()) {
            foreach ($this->children as $child) {
                if ($child->isActive()) {
                    return true;
                }
            }

            return false;
        }

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
        if ($this->showsChildrenInSidebar()) {
            return '#';
        }

        if ($this->isGroup()) {
            return route('navigation.hub', $this->groupId);
        }

        return route($this->route);
    }

    /**
     * @param  list<self>  $children
     */
    public function withChildren(array $children): self
    {
        $this->children = $children;

        return $this;
    }
}
