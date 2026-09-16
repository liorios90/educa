<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNavigationItemRequest;
use App\Http\Requests\UpdateNavigationItemRequest;
use App\Models\NavigationItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class NavigationItemController extends Controller
{
    /**
     * @var list<string>
     */
    private const SORTABLE = [
        'sort_order',
        'label',
        'route_name',
        'is_group',
        'visible_to_all',
        'is_active',
    ];

    public function index(Request $request): View
    {
        [$sort, $direction] = $this->sortFrom($request);
        $search = $this->searchFrom($request);

        $items = NavigationItem::query()
            ->topLevel()
            ->with('roles');

        $this->applySearch($items, $search);

        return view('sistemas.navigation-items.index', [
            'items' => $items
                ->orderBy($sort, $direction)
                ->orderBy('id')
                ->paginate(15)
                ->withQueryString(),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('sistemas.navigation-items.create', $this->formData());
    }

    public function store(StoreNavigationItemRequest $request): RedirectResponse
    {
        $item = NavigationItem::create($this->attributesFrom($request));

        $this->syncRoles($item, $request);

        return redirect()
            ->route('sistemas.navigation-items.index')
            ->with('status', 'navigation-item-created');
    }

    public function edit(NavigationItem $navigationItem): View
    {
        abort_unless($navigationItem->parent_id === null, 404);

        $navigationItem->load('roles');

        return view('sistemas.navigation-items.edit', [
            ...$this->formData(),
            'item' => $navigationItem,
        ]);
    }

    public function update(UpdateNavigationItemRequest $request, NavigationItem $navigationItem): RedirectResponse
    {
        abort_unless($navigationItem->parent_id === null, 404);

        $navigationItem->update($this->attributesFrom($request));
        $this->syncRoles($navigationItem, $request);
        $navigationItem->load('roles');

        $navigationItem->children()->each(
            fn (NavigationItem $child) => $child->copyVisibilityFrom($navigationItem),
        );

        return redirect()
            ->route('sistemas.navigation-items.index')
            ->with('status', 'navigation-item-updated');
    }

    public function destroy(NavigationItem $navigationItem): RedirectResponse
    {
        abort_unless($navigationItem->parent_id === null, 404);

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

    /**
     * @return array<string, mixed>
     */
    private function attributesFrom(StoreNavigationItemRequest $request): array
    {
        $isGroup = $request->boolean('is_group');

        return [
            ...$request->safe()->only([
                'label',
                'icon',
                'sort_order',
                'is_active',
                'visible_to_all',
                'is_group',
            ]),
            'parent_id' => null,
            'route_name' => $isGroup ? 'navigation.hub' : $request->validated('route_name'),
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

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    private function sortFrom(Request $request): array
    {
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString();

        return [
            in_array($sort, self::SORTABLE, true) ? $sort : 'sort_order',
            $direction === 'desc' ? 'desc' : 'asc',
        ];
    }

    private function searchFrom(Request $request): string
    {
        return $request->string('q')->trim()->substr(0, 100)->toString();
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%'.addcslashes($search, '%_\\').'%';
        $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query->where(function (Builder $builder) use ($like, $operator): void {
            $builder->where('label', $operator, $like)
                ->orWhere('route_name', $operator, $like)
                ->orWhereHas('roles', function (Builder $roles) use ($like, $operator): void {
                    $roles->where('name', $operator, $like);
                });
        });
    }
}
