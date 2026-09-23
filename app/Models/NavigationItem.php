<?php

namespace App\Models;

use App\Enums\NavigationGroupDisplay;
use App\Enums\Role;
use App\Navigation\NavigationItem as MenuItem;
use Database\Factories\NavigationItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role as RoleModel;

#[Fillable(['parent_id', 'label', 'route_name', 'icon', 'sort_order', 'is_active', 'visible_to_all', 'is_group', 'group_display'])]
class NavigationItem extends Model
{
    /** @use HasFactory<NavigationItemFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'group_display' => 'screen',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'visible_to_all' => 'boolean',
            'is_group' => 'boolean',
            'group_display' => NavigationGroupDisplay::class,
        ];
    }

    /**
     * @return BelongsTo<NavigationItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<NavigationItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsToMany<RoleModel, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'navigation_item_role');
    }

    public function isVisibleTo(User $user, ?Role $activeRole = null): bool
    {
        if ($this->visible_to_all) {
            return true;
        }

        $roleNames = $this->roles->pluck('name')->all();

        if ($activeRole !== null) {
            return in_array($activeRole->value, $roleNames, true);
        }

        return $user->hasAnyRole($roleNames);
    }

    public function copyVisibilityFrom(NavigationItem $parent): void
    {
        $this->update([
            'visible_to_all' => $parent->visible_to_all,
            'is_group' => false,
            'group_display' => NavigationGroupDisplay::Screen,
        ]);

        $this->roles()->sync($parent->visible_to_all ? [] : $parent->roles()->pluck('id'));
    }

    public function displayedRoute(): string
    {
        if (! $this->is_group) {
            return $this->route_name;
        }

        return $this->groupDisplay()->displayedRoute();
    }

    public function displaysChildrenInSidebar(): bool
    {
        return $this->is_group && $this->groupDisplay() === NavigationGroupDisplay::Sidebar;
    }

    public function displaysAsScreen(): bool
    {
        return $this->is_group && $this->groupDisplay() === NavigationGroupDisplay::Screen;
    }

    public function hubBackUrl(): ?string
    {
        $parent = $this->parent;

        if (! $parent instanceof self || ! $parent->displaysAsScreen()) {
            return null;
        }

        return route('navigation.hub', $parent);
    }

    public static function hubBackUrlForRoute(string $routeName): ?string
    {
        $item = static::query()
            ->with('parent')
            ->where('route_name', $routeName)
            ->whereNotNull('parent_id')
            ->first();

        return $item?->hubBackUrl();
    }

    public function groupDisplay(): NavigationGroupDisplay
    {
        return $this->group_display ?? NavigationGroupDisplay::Screen;
    }

    public function displayedVisibility(): string
    {
        if ($this->visible_to_all) {
            return 'Todos';
        }

        $labels = $this->roles
            ->map(fn (RoleModel $role): string => Role::tryFrom($role->name)?->label() ?? $role->name)
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return $labels->isNotEmpty() ? $labels->implode(', ') : 'Sin roles';
    }

    public function toMenuItem(): MenuItem
    {
        /** @var list<Role> $roles */
        $roles = $this->visible_to_all
            ? []
            : $this->roles
                ->map(fn (RoleModel $role): ?Role => Role::tryFrom($role->name))
                ->filter()
                ->values()
                ->all();

        return new MenuItem(
            $this->label,
            $this->route_name,
            $this->icon,
            $roles,
            $this->is_group ? $this->id : null,
            $this->groupDisplay(),
        );
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function topLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
