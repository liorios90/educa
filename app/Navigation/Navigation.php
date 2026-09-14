<?php

namespace App\Navigation;

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
        return NavigationItemModel::query()
            ->topLevel()
            ->active()
            ->with('roles')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (NavigationItemModel $item): bool => $this->isReachable($item) && $item->isVisibleTo($user))
            ->map(fn (NavigationItemModel $item): NavigationItem => $item->toMenuItem())
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
