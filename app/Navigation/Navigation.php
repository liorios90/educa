<?php

namespace App\Navigation;

use App\Enums\Role;
use App\Models\User;

class Navigation
{
    /**
     * @return list<NavigationItem>
     */
    public function itemsFor(User $user): array
    {
        return array_values(array_filter(
            $this->items(),
            fn (NavigationItem $item): bool => $item->visibleTo($user),
        ));
    }

    /**
     * @return list<NavigationItem>
     */
    private function items(): array
    {
        return [
            new NavigationItem('Inicio', 'dashboard', 'home'),
            new NavigationItem('Usuarios', 'admin.users', 'users', [Role::Admin]),
            new NavigationItem('Sistemas', 'sistemas.home', 'cog', [Role::Sistemas]),
            new NavigationItem('Perfil', 'profile.edit', 'user'),
        ];
    }
}
