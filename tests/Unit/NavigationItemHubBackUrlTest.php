<?php

use App\Enums\NavigationGroupDisplay;
use App\Models\NavigationItem;
use Tests\TestCase;

uses(TestCase::class);

it('returns the hub url when the parent is a screen of buttons', function () {
    $parent = new NavigationItem([
        'is_group' => true,
        'group_display' => NavigationGroupDisplay::Screen,
    ]);
    $parent->id = 12;
    $child = new NavigationItem;
    $child->setRelation('parent', $parent);

    expect($child->hubBackUrl())->toBe(route('navigation.hub', 12));
});

it('returns null when the parent is a left sidebar submenu', function () {
    $parent = new NavigationItem([
        'is_group' => true,
        'group_display' => NavigationGroupDisplay::Sidebar,
    ]);
    $parent->id = 12;
    $child = new NavigationItem;
    $child->setRelation('parent', $parent);

    expect($child->hubBackUrl())->toBeNull();
});

it('returns null when the item has no parent', function () {
    $child = new NavigationItem;
    $child->setRelation('parent', null);

    expect($child->hubBackUrl())->toBeNull();
});
