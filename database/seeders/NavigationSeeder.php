<?php

namespace Database\Seeders;

use App\Enums\NavigationGroupDisplay;
use App\Enums\Role as RoleName;
use App\Models\NavigationItem;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class NavigationSeeder extends Seeder
{
    /**
     * Seed missing menu items only. Existing items keep the label, icon, order, visibility and roles configured in Sistemas.
     */
    public function run(): void
    {
        $admin = Role::findOrCreate(RoleName::Admin->value, 'web');
        $secretaria = Role::findOrCreate(RoleName::Secretaria->value, 'web');
        $sistemas = Role::findOrCreate(RoleName::Sistemas->value, 'web');
        $padre = Role::findOrCreate(RoleName::Padre->value, 'web');
        $docente = Role::findOrCreate(RoleName::Docente->value, 'web');

        $this->ensureItem('Inicio', 'dashboard', 'home', 1, true, []);
        $this->ensureItem('Matrícula', 'matriculas.index', 'clipboard-document-list', 1, false, [$padre->id]);

        $mantenimiento = $this->ensureItem('Matenimiento', 'navigation.hub', 'wrench-screwdriver', 2, false, [$admin->id], isGroup: true, groupDisplay: NavigationGroupDisplay::Sidebar);
        $this->ensureItem('Padres', 'Admin.padres', 'users', 1, false, [$admin->id], parentId: $mantenimiento->id);
        $this->ensureItem('Alumnos', 'Admin.alumnos', 'academic-cap', 2, false, [$admin->id], parentId: $mantenimiento->id);
        $this->ensureItem('Empleados', 'Admin.empleados', 'clipboard-document-list', 3, false, [$admin->id], parentId: $mantenimiento->id);

        $this->ensureItem('Horario', 'Docente.horarios', 'table-cells', 2, false, [$docente->id]);

        $estructuraInstitucional = $this->ensureItem('Estructura Institucional', 'navigation.hub', 'squares-2x2', 3, false, [$admin->id], isGroup: true, groupDisplay: NavigationGroupDisplay::Sidebar);
        $this->ensureItem('Nivel - Subnivel - Grado', 'Admin.estructura', 'building-office-2', 1, false, [$admin->id], parentId: $estructuraInstitucional->id);
        $this->ensureItem('Areas y Asignaturas', 'Admin.asignaturas', 'book-open', 2, false, [$admin->id], parentId: $estructuraInstitucional->id);
        $this->ensureItem('Docentes', 'Admin.docentes', 'user', 3, false, [$admin->id], parentId: $estructuraInstitucional->id);
        $this->ensureItem('Aulas', 'Admin.aulas', 'building-library', 4, false, [$admin->id], parentId: $estructuraInstitucional->id);
        $this->ensureItem('Grupos por Grado', 'Admin.grupos-asignatura.flotantes', 'users', 5, false, [$admin->id], parentId: $estructuraInstitucional->id);
        $this->ensureItem('Programas', 'Admin.programas.index', 'map', 6, false, [$admin->id, $secretaria->id], parentId: $estructuraInstitucional->id);

        $this->ensureItem('Sistemas', 'sistemas.home', 'cog', 3, false, [$sistemas->id]);

        $insumos = $this->ensureItem('Insumos', 'navigation.hub', 'academic-cap', 3, false, [$docente->id], isGroup: true);
        $this->ensureItem('Nuevo Insumo', 'Docente.calificar.index', 'book-open', 1, false, [$docente->id], parentId: $insumos->id);
        $this->ensureItem('Editar Insumo', 'Docente.calificar.editar', 'folder', 2, false, [$docente->id], parentId: $insumos->id);

        $this->ensureItem('Menú', 'sistemas.navigation-items.index', 'cog', 4, false, [$sistemas->id]);

        $catalogos = $this->ensureItem('Catálogos', 'navigation.hub', 'cog', 5, false, [$sistemas->id], isGroup: true);
        $this->ensureItem('Jornadas', 'sistemas.crud.jornadas.index', 'cog', 1, false, [$sistemas->id], parentId: $catalogos->id);
        $this->ensureItem('Modalidades', 'sistemas.crud.modalidades.index', 'cog', 2, false, [$sistemas->id], parentId: $catalogos->id);
        $this->ensureItem('Zonas', 'sistemas.crud.zonas.index', 'cog', 3, false, [$sistemas->id], parentId: $catalogos->id);
        $this->ensureItem('Distritos', 'sistemas.crud.distritos.index', 'cog', 4, false, [$sistemas->id], parentId: $catalogos->id);
        $this->ensureItem('Circuitos', 'sistemas.crud.circuitos.index', 'cog', 5, false, [$sistemas->id], parentId: $catalogos->id);
        $this->ensureItem('Países', 'sistemas.crud.paises.index', 'cog', 6, false, [$sistemas->id], parentId: $catalogos->id);
        $this->ensureItem('Provincias', 'sistemas.crud.provincias.index', 'cog', 7, false, [$sistemas->id], parentId: $catalogos->id);
        $this->ensureItem('Funciones', 'sistemas.crud.funciones.index', 'cog', 8, false, [$sistemas->id], parentId: $catalogos->id);
        $this->ensureItem('Tipos de contrato', 'sistemas.crud.tipos-contratos.index', 'cog', 9, false, [$sistemas->id], parentId: $catalogos->id);

        $this->ensureItem('Asistente', 'secretaria.asistente', 'wrench-screwdriver', 5, false, [$secretaria->id]);
        $this->ensureItem('Diseñar reportes', 'sistemas.reports.index', 'cog', 6, false, [$sistemas->id]);
        $this->ensureItem('Asistente', 'Docente.asistente', 'globe-alt', 6, false, [$docente->id]);

        $estructura = $this->ensureItem('Estructura', 'navigation.hub', 'home', 7, false, [$sistemas->id], isGroup: true);
        $this->ensureItem('Nivel - Subnivel - Grado', 'sistemas.estructura', 'home', 1, false, [$sistemas->id], parentId: $estructura->id);
        $this->ensureItem('Áreas y asignaturas', 'sistemas.curriculo', 'home', 2, false, [$sistemas->id], parentId: $estructura->id);

        $configuracion = $this->ensureItem('Configuración', 'navigation.hub', 'cog', 7, false, [$admin->id], isGroup: true, groupDisplay: NavigationGroupDisplay::Sidebar);
        $this->ensureItem('Período', 'Admin.periodo', 'calendar-days', 1, false, [$admin->id], parentId: $configuracion->id);
        $this->ensureItem('Por grados', 'Admin.configuracion-grados.index', 'cog', 2, false, [$admin->id], parentId: $configuracion->id);
        $this->ensureItem('Por Asignaturas', 'Admin.configuracion-asignaturas.index', 'book-open', 3, false, [$admin->id], parentId: $configuracion->id);
        $this->ensureItem('Tipos de Insumos', 'Admin.tipos-insumos.index', 'calendar-days', 4, false, [$admin->id], parentId: $configuracion->id);
        $this->ensureItem('Tablas de Equivalencias', 'Admin.tablas-equivalencias.index', 'table-cells', 5, false, [$admin->id], parentId: $configuracion->id);
        $this->ensureItem('Asistente', 'Admin.asistente', 'sparkles', 6, false, [$admin->id], parentId: $configuracion->id);

        $this->ensureItem('Reportes', 'reports.index', 'home', 8, true, []);
        $this->ensureItem('Establecimientos', 'sistemas.establecimientos', 'building-library', 8, false, [$sistemas->id]);
        $this->ensureItem('Proceso de Cierres', 'Admin.proceso-cierres.index', 'clipboard-document-list', 8, false, [$admin->id]);
        $this->ensureItem('Perfil', 'profile.edit', 'user', 9, true, [], isActive: false);

        $matriculas = $this->ensureItem('Matriculas', 'navigation.hub', 'document-text', 9, false, [$admin->id, $secretaria->id], isGroup: true, groupDisplay: NavigationGroupDisplay::Sidebar);
        $this->ensureItem('Formularios', 'Admin.matriculas.index', 'clipboard-document-list', 1, false, [$admin->id, $secretaria->id], parentId: $matriculas->id);
        $this->ensureItem('Matricular Alumno', 'Admin.grupos-asignatura.index', 'user', 2, false, [$admin->id, $secretaria->id], parentId: $matriculas->id);

        $this->ensureItem('Grupos flotantes', 'Admin.grupos-asignatura.flotantes', 'academic-cap', 11, false, [$admin->id, $secretaria->id]);
    }

    /**
     * @param  list<int>  $roleIds
     */
    private function ensureItem(
        string $label,
        string $routeName,
        string $icon,
        int $sortOrder,
        bool $visibleToAll,
        array $roleIds,
        bool $isGroup = false,
        ?int $parentId = null,
        NavigationGroupDisplay $groupDisplay = NavigationGroupDisplay::Screen,
        bool $isActive = true,
    ): NavigationItem {
        $item = $this->existingItem($routeName, $isGroup, $parentId, $label);

        if ($item instanceof NavigationItem) {
            return $item;
        }

        $item = NavigationItem::query()->create([
            'label' => $label,
            'route_name' => $routeName,
            'icon' => $icon,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
            'visible_to_all' => $visibleToAll,
            'is_group' => $isGroup,
            'parent_id' => $parentId,
            'group_display' => $groupDisplay,
        ]);

        $item->roles()->sync($visibleToAll ? [] : $roleIds);

        return $item;
    }

    private function existingItem(string $routeName, bool $isGroup, ?int $parentId, string $label): ?NavigationItem
    {
        $query = NavigationItem::query()
            ->where('route_name', $routeName)
            ->where('is_group', $isGroup)
            ->orderBy('id');

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        if ($isGroup) {
            $query->where('label', $label);
        }

        return $query->first();
    }
}
