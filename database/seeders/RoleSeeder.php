<?php

namespace Database\Seeders;

use App\Enums\Role as RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = Role::findOrCreate(RoleName::Admin->value, 'web');
        Role::findOrCreate(RoleName::Sistemas->value, 'web');
        Role::findOrCreate(RoleName::Secretaria->value, 'web');
        Role::findOrCreate(RoleName::Padre->value, 'web');

        $adminHome = Permission::findOrCreate('admin.home', 'web');
        $admin->givePermissionTo($adminHome);
    }
}
