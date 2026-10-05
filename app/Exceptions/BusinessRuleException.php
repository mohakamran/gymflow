<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thrown when an action breaks a business rule (full class, plan limit reached, ...).
 * Rendered as a friendly error toast instead of a 500.
 */
class BusinessRuleException extends \RuntimeException
{
    public function render(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 422);
        }

        return back()->withInput()->with('toast', ['type' => 'error', 'message' => $this->getMessage()]);
    }
}
