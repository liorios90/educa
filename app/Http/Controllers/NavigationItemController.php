<?php

namespace App\Http\Controllers;

use App\Enums\Role as RoleName;
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

    private const PER_PAGE = 10;

    public function index(Request $request): View
    {
        [$sort, $direction] = $this->sortFrom($request);
        $search = $this->searchFrom($request);

        $items = NavigationItem::query()
            ->topLevel()
            ->with('roles');

        $this->applySearch($items, $search);
        $this->applySort($items, $sort, $direction);

        return view('sistemas.navigation-items.index', [
            'items' => $items
                ->paginate(self::PER_PAGE)
                ->appends($this->listQuery($sort, $direction, $search)),
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

    /**
     * @return array<string, string>
     */
    private function listQuery(string $sort, string $direction, string $search): array
    {
        return array_filter([
            'sort' => $sort,
            'direction' => $direction,
            'q' => $search !== '' ? $search : null,
        ], fn (mixed $value): bool => $value !== null);
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%'.addcslashes($this->foldAccents($search), '%_\\').'%';

        $query->where(function (Builder $builder) use ($like): void {
            $table = $builder->getModel()->getTable();

            $builder->whereRaw($this->foldAccentsSql($table.'.label').' like ?', [$like])
                ->orWhereRaw($this->foldAccentsSql($table.'.route_name').' like ?', [$like])
                ->orWhereHas('roles', function (Builder $roles) use ($like): void {
                    $rolesTable = $roles->getModel()->getTable();
                    $roles->whereRaw($this->foldAccentsSql($rolesTable.'.name').' like ?', [$like]);
                })
                ->orWhereHas('children', function (Builder $children) use ($like): void {
                    $childrenTable = $children->getModel()->getTable();
                    $children->where(function (Builder $nested) use ($childrenTable, $like): void {
                        $nested->whereRaw($this->foldAccentsSql($childrenTable.'.label').' like ?', [$like])
                            ->orWhereRaw($this->foldAccentsSql($childrenTable.'.route_name').' like ?', [$like]);
                    });
                });
        });
    }

    private function applySort(Builder $query, string $sort, string $direction): void
    {
        $directionSql = $direction === 'desc' ? 'desc' : 'asc';
        $table = $query->getModel()->getTable();

        $expression = match ($sort) {
            'label' => $table.'.label',
            'route_name' => $this->routeDisplaySql($table),
            'visible_to_all' => $this->visibilityDisplaySql($table),
            default => null,
        };

        if ($expression !== null) {
            $query->orderByRaw($this->foldAccentsSql($expression).' '.$directionSql);
        } else {
            $query->orderBy($sort, $direction);
        }

        if ($sort !== 'label') {
            $query->orderByRaw($this->foldAccentsSql($table.'.label').' asc');
        }

        $query->orderBy($table.'.id');
    }

    private function routeDisplaySql(string $table): string
    {
        return "CASE WHEN {$table}.is_group THEN 'Botones' ELSE {$table}.route_name END";
    }

    private function visibilityDisplaySql(string $table): string
    {
        $roles = (new NavigationItem)->roles();
        $pivot = $roles->getTable();
        $rolesTable = $roles->getRelated()->getTable();
        $roleLabel = $this->roleLabelSql($rolesTable.'.name');
        $aggregated = "(select min({$roleLabel}) from {$pivot} inner join {$rolesTable} on {$rolesTable}.id = {$pivot}.role_id where {$pivot}.navigation_item_id = {$table}.id)";

        return "CASE WHEN {$table}.visible_to_all THEN 'Todos' ELSE coalesce({$aggregated}, 'Sin roles') END";
    }

    private function roleLabelSql(string $nameColumn): string
    {
        $cases = '';

        foreach (RoleName::cases() as $role) {
            if ($role->label() === $role->value) {
                continue;
            }

            $cases .= ' WHEN '.$this->sqlString($role->value).' THEN '.$this->sqlString($role->label());
        }

        return "CASE {$nameColumn}{$cases} ELSE {$nameColumn} END";
    }

    private function sqlString(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }

    private function foldAccents(string $value): string
    {
        return strtr(mb_strtolower($value), [
            'á' => 'a',
            'à' => 'a',
            'ä' => 'a',
            'â' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ë' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'ï' => 'i',
            'î' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ö' => 'o',
            'ô' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'ü' => 'u',
            'û' => 'u',
            'ñ' => 'n',
        ]);
    }

    private function foldAccentsSql(string $expression): string
    {
        $sql = 'lower(('.$expression.'))';

        foreach ([
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
            'Á' => 'a',
            'É' => 'e',
            'Í' => 'i',
            'Ó' => 'o',
            'Ú' => 'u',
            'Ü' => 'u',
            'Ñ' => 'n',
        ] as $from => $to) {
            $sql = 'replace('.$sql.", '".$from."', '".$to."')";
        }

        return $sql;
    }
}
