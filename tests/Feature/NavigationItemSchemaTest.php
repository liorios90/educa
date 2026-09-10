<?php

use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates the navigation tables', function () {
    expect(Schema::hasTable('navigation_items'))->toBeTrue()
        ->and(Schema::hasTable('navigation_item_role'))->toBeTrue();
});

it('removes role assignments when a navigation item is deleted', function () {
    $this->seed(RoleSeeder::class);

    $itemId = DB::table('navigation_items')->insertGetId([
        'label' => 'Usuarios',
        'route_name' => 'admin.users',
        'icon' => 'users',
        'sort_order' => 1,
        'is_active' => true,
        'visible_to_all' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $roleId = DB::table('roles')->where('name', 'Admin')->value('id');

    DB::table('navigation_item_role')->insert([
        'navigation_item_id' => $itemId,
        'role_id' => $roleId,
    ]);

    DB::table('navigation_items')->where('id', $itemId)->delete();

    expect(DB::table('navigation_item_role')->where('navigation_item_id', $itemId)->exists())->toBeFalse();
});
