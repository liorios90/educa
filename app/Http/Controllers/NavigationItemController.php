<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNavigationItemRequest;
use App\Http\Requests\UpdateNavigationItemRequest;
use App\Models\NavigationItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class NavigationItemController extends Controller
{
    public function index(): View
    {
        return view('sistemas.navigation-items.index', [
            'items' => NavigationItem::query()
                ->with('roles')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('sistemas.navigation-items.create', $this->formData());
    }

    public function store(StoreNavigationItemRequest $request): RedirectResponse
    {
        $item = NavigationItem::create($request->safe()->only([
            'label',
            'route_name',
            'icon',
            'sort_order',
            'is_active',
            'visible_to_all',
        ]));

        $this->syncRoles($item, $request);

        return redirect()
            ->route('sistemas.navigation-items.index')
            ->with('status', 'navigation-item-created');
    }

    public function edit(NavigationItem $navigationItem): View
    {
        $navigationItem->load('roles');

        return view('sistemas.navigation-items.edit', [
            ...$this->formData(),
            'item' => $navigationItem,
        ]);
    }

    public function update(UpdateNavigationItemRequest $request, NavigationItem $navigationItem): RedirectResponse
    {
        $navigationItem->update($request->safe()->only([
            'label',
            'route_name',
            'icon',
            'sort_order',
            'is_active',
            'visible_to_all',
        ]));

        $this->syncRoles($navigationItem, $request);

        return redirect()
            ->route('sistemas.navigation-items.index')
            ->with('status', 'navigation-item-updated');
    }

    public function destroy(NavigationItem $navigationItem): RedirectResponse
    {
        $navigationItem->delete();

        return redirect()
            ->route('sistemas.navigation-items.index')
            ->with('status', 'navigation-item-deleted');
    }

    /**
     * @return array{roles: \Illuminate\Database\Eloquent\Collection<int, Role>, icons: list<string>, routeNames: Collection<int, string>}
     */
    private function formData(): array
    {
        return [
            'roles' => Role::query()->orderBy('name')->get(),
            'icons' => NavigationItem::ICONS,
            'routeNames' => collect(Route::getRoutes()->getRoutesByName())->keys()->sort()->values(),
        ];
    }

    private function syncRoles(NavigationItem $item, StoreNavigationItemRequest $request): void
    {
        if ($request->boolean('visible_to_all')) {
            $item->roles()->sync([]);

            return;
        }

        $item->roles()->sync($request->validated('roles'));
    }
}
