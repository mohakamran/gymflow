<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Redirect with a success toast.
     */
    protected function done(RedirectResponse $redirect, string $message, string $type = 'success'): RedirectResponse
    {
        return $redirect->with('toast', ['type' => $type, 'message' => $message]);
    }
}
