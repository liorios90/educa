<?php

namespace Database\Seeders;

use App\Enums\Role as RoleName;
use App\Models\NavigationItem;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class NavigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::findOrCreate(RoleName::Admin->value, 'web');
        $sistemas = Role::findOrCreate(RoleName::Sistemas->value, 'web');

        $this->upsertItem('Inicio', 'dashboard', 'home', 1, true, []);
        $this->upsertItem('Usuarios', 'admin.users', 'users', 2, false, [$admin->id]);
        $this->upsertItem('Sistemas', 'sistemas.home', 'cog', 3, false, [$sistemas->id]);
        $this->upsertItem('Menú', 'sistemas.navigation-items.index', 'cog', 4, false, [$sistemas->id]);
        $catalogos = $this->upsertItem('Catálogos', 'navigation.hub', 'cog', 5, false, [$sistemas->id], isGroup: true);
        $this->upsertItem('Jornadas', 'sistemas.crud.jornadas.index', 'cog', 1, false, [$sistemas->id], parentId: $catalogos->id);
        $this->upsertItem('Modalidades', 'sistemas.crud.modalidades.index', 'cog', 2, false, [$sistemas->id], parentId: $catalogos->id);
        $this->upsertItem('Zonas', 'sistemas.crud.zonas.index', 'cog', 3, false, [$sistemas->id], parentId: $catalogos->id);
        $this->upsertItem('Distritos', 'sistemas.crud.distritos.index', 'cog', 4, false, [$sistemas->id], parentId: $catalogos->id);
        $this->upsertItem('Circuitos', 'sistemas.crud.circuitos.index', 'cog', 5, false, [$sistemas->id], parentId: $catalogos->id);
        $this->upsertItem('Diseñar reportes', 'sistemas.reports.index', 'cog', 6, false, [$sistemas->id]);
        $estructura = $this->upsertItem('Estructura', 'navigation.hub', 'home', 7, false, [$sistemas->id], isGroup: true);
        $this->upsertItem('Nivel - Subnivel - Grado', 'sistemas.estructura', 'home', 1, false, [$sistemas->id], parentId: $estructura->id);
        $this->upsertItem('Reportes', 'reports.index', 'home', 8, true, []);
        $this->upsertItem('Perfil', 'profile.edit', 'user', 9, true, []);
    }

    /**
     * @param  list<int>  $roleIds
     */
    private function upsertItem(
        string $label,
        string $routeName,
        string $icon,
        int $sortOrder,
        bool $visibleToAll,
        array $roleIds,
        bool $isGroup = false,
        ?int $parentId = null,
    ): NavigationItem {
        $item = NavigationItem::query()->updateOrCreate(
            [
                'route_name' => $routeName,
                'label' => $label,
            ],
            [
                'icon' => $icon,
                'sort_order' => $sortOrder,
                'is_active' => true,
                'visible_to_all' => $visibleToAll,
                'is_group' => $isGroup,
                'parent_id' => $parentId,
            ],
        );

        $item->roles()->sync($visibleToAll ? [] : $roleIds);

        return $item;
    }
}
