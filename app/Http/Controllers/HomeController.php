<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Send each user to the right area of the app for their role.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        return match (true) {
            $user->isSuperAdmin() => redirect()->route('admin.dashboard'),
            $user->primaryRole() === Role::Member => redirect()->route('portal.dashboard'),
            default => redirect()->route('dashboard'),
        };
    }
}
