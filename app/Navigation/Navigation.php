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
            ->active()
            ->with('roles')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (NavigationItemModel $item): bool => Route::has($item->route_name) && $item->isVisibleTo($user))
            ->map(fn (NavigationItemModel $item): NavigationItem => $item->toMenuItem())
            ->values()
            ->all();
    }
}
