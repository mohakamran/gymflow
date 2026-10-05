<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterGymRequest;
use App\Services\TenantRegistrationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredGymController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterGymRequest $request, TenantRegistrationService $registration): RedirectResponse
    {
        ['owner' => $owner] = $registration->register($request->validated());

        event(new Registered($owner));

        Auth::login($owner);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }
}
