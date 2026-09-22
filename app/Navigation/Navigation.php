<?php

namespace App\Navigation;

use App\Auth\ActiveRole;
use App\Enums\Role;
use App\Models\NavigationItem as NavigationItemModel;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class Navigation
{
    /**
     * @return list<NavigationItem>
     */
    public function itemsFor(User $user): array
    {
        $activeRole = app(ActiveRole::class)->get($user);

        return NavigationItemModel::query()
            ->topLevel()
            ->active()
            ->with(['roles', 'children.roles'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (NavigationItemModel $item): bool => $this->isReachable($item) && $item->isVisibleTo($user, $activeRole))
            ->map(fn (NavigationItemModel $item): NavigationItem => $item
                ->toMenuItem()
                ->withChildren($this->childMenuItems($item, $user, $activeRole)))
            ->values()
            ->all();
    }

    /**
     * @return list<NavigationItem>
     */
    private function childMenuItems(NavigationItemModel $item, User $user, ?Role $activeRole): array
    {
        if (! $item->displaysChildrenInSidebar()) {
            return [];
        }

        return $item->children
            ->filter(fn (NavigationItemModel $child): bool => $child->is_active
                && $this->isReachable($child)
                && $child->isVisibleTo($user, $activeRole))
            ->map(fn (NavigationItemModel $child): NavigationItem => $child->toMenuItem())
            ->values()
            ->all();
    }

    private function isReachable(NavigationItemModel $item): bool
    {
        if ($item->is_group) {
            return true;
        }

        return is_string($item->route_name) && Route::has($item->route_name);
    }
}
