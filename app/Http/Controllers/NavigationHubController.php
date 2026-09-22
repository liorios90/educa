<?php

namespace App\Http\Controllers;

use App\Models\NavigationItem;
use App\Navigation\HubGuide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class NavigationHubController extends Controller
{
    public function show(Request $request, NavigationItem $navigationItem): View
    {
        abort_unless($navigationItem->displaysAsScreen() && $navigationItem->parent_id === null && $navigationItem->is_active, 404);

        $user = $request->user();
        abort_unless($user !== null && $navigationItem->isVisibleTo($user), 404);

        $navigationItem->load(['roles', 'children.roles']);

        $buttons = $navigationItem->children
            ->filter(fn (NavigationItem $child): bool => $child->is_active
                && is_string($child->route_name)
                && Route::has($child->route_name)
                && $child->isVisibleTo($user))
            ->values();

        return view('navigation.hub', [
            'item' => $navigationItem,
            'intro' => HubGuide::intro($navigationItem->label),
            'sections' => HubGuide::sections($buttons),
        ]);
    }
}
