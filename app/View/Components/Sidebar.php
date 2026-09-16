<?php

namespace App\View\Components;

use App\Models\User;
use App\Navigation\Navigation;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class Sidebar extends Component
{
    public function __construct(private Navigation $navigation) {}

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $user->loadMissing('establecimiento');

        return view('components.sidebar', [
            'items' => $this->navigation->itemsFor($user),
            'user' => $user,
            'establecimiento' => $user->establecimiento,
        ]);
    }
}
