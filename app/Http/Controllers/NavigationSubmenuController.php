<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNavigationSubmenuRequest;
use App\Models\NavigationItem;
use App\Navigation\NavigationIcons;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class NavigationSubmenuController extends Controller
{
    public function index(NavigationItem $navigationItem): View
    {
        $this->ensureGroup($navigationItem);

        return view('sistemas.navigation-items.submenus.index', [
            'parent' => $navigationItem,
            'items' => $navigationItem->children()->with('roles')->get(),
        ]);
    }

    public function create(NavigationItem $navigationItem): View
    {
        $this->ensureGroup($navigationItem);

        return view('sistemas.navigation-items.submenus.create', [
            'parent' => $navigationItem,
            ...$this->formData(),
        ]);
    }

    public function store(StoreNavigationSubmenuRequest $request, NavigationItem $navigationItem): RedirectResponse
    {
        $this->ensureGroup($navigationItem);

        $child = $navigationItem->children()->create([
            ...$request->safe()->only(['label', 'route_name', 'icon', 'sort_order', 'is_active']),
            'is_group' => false,
        ]);

        $navigationItem->load('roles');
        $child->copyVisibilityFrom($navigationItem);

        return redirect()
            ->route('sistemas.navigation-items.submenus.index', $navigationItem)
            ->with('status', 'navigation-submenu-created');
    }

    public function edit(NavigationItem $navigationItem, string $submenu): View
    {
        $child = $this->child($navigationItem, $submenu);

        return view('sistemas.navigation-items.submenus.edit', [
            'parent' => $navigationItem,
            'item' => $child,
            ...$this->formData(),
        ]);
    }

    public function update(StoreNavigationSubmenuRequest $request, NavigationItem $navigationItem, string $submenu): RedirectResponse
    {
        $child = $this->child($navigationItem, $submenu);

        $child->update($request->safe()->only(['label', 'route_name', 'icon', 'sort_order', 'is_active']));
        $navigationItem->load('roles');
        $child->copyVisibilityFrom($navigationItem);

        return redirect()
            ->route('sistemas.navigation-items.submenus.index', $navigationItem)
            ->with('status', 'navigation-submenu-updated');
    }

    public function destroy(NavigationItem $navigationItem, string $submenu): RedirectResponse
    {
        $child = $this->child($navigationItem, $submenu);

        $child->delete();

        return redirect()
            ->route('sistemas.navigation-items.submenus.index', $navigationItem)
            ->with('status', 'navigation-submenu-deleted');
    }

    /**
     * @return array{icons: array<string, array{label: string, paths: list<string>}>, routeNames: Collection<int, string>}
     */
    private function formData(): array
    {
        return [
            'icons' => NavigationIcons::catalog(),
            'routeNames' => collect(Route::getRoutes()->getRoutesByName())->keys()->sort()->values(),
        ];
    }

    private function ensureGroup(NavigationItem $parent): void
    {
        abort_unless($parent->is_group && $parent->parent_id === null, 404);
    }

    private function child(NavigationItem $parent, string $submenu): NavigationItem
    {
        $this->ensureGroup($parent);

        return $parent->children()->findOrFail((int) $submenu);
    }
}
