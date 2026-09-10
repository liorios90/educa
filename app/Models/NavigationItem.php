<?php

namespace App\Models;

use App\Enums\Role;
use App\Navigation\NavigationItem as MenuItem;
use Database\Factories\NavigationItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as RoleModel;

#[Fillable(['label', 'route_name', 'icon', 'sort_order', 'is_active', 'visible_to_all'])]
class NavigationItem extends Model
{
    /** @use HasFactory<NavigationItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    public const ICONS = ['home', 'users', 'cog', 'user'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'visible_to_all' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<RoleModel, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'navigation_item_role');
    }

    public function isVisibleTo(User $user): bool
    {
        if ($this->visible_to_all) {
            return true;
        }

        return $user->hasAnyRole($this->roles->pluck('name')->all());
    }

    public function toMenuItem(): MenuItem
    {
        if ($this->visible_to_all) {
            return new MenuItem($this->label, $this->route_name, $this->icon);
        }

        /** @var list<Role> $roles */
        $roles = $this->roles
            ->map(fn (RoleModel $role): ?Role => Role::tryFrom($role->name))
            ->filter()
            ->values()
            ->all();

        return new MenuItem($this->label, $this->route_name, $this->icon, $roles);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
