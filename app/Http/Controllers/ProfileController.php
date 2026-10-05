<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'email', 'phone']));

        if ($request->hasFile('avatar')) {
            $old = $user->avatar_path;
            $user->avatar_path = $request->file('avatar')->store('avatars/'.($user->tenant_id ?? 'platform'), 'public');

            if ($old) {
                Storage::disk('public')->delete($old);
            }
        }

        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return redirect()->route('profile.edit')->with('toast', ['type' => 'success', 'message' => 'Profile updated.']);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return redirect()->route('profile.edit')->with('toast', ['type' => 'success', 'message' => 'Password changed.']);
    }
}
