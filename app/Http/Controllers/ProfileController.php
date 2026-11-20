<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's account profile.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the authenticated user's personal information.
     */
    public function update(
        UpdateProfileRequest $request,
        ProfileService $profileService
    ): RedirectResponse {
        $profileService->update(
            $request->user(),
            $request->validated()
        );

        $request->user()->refresh();

        return redirect()
            ->route('profile.edit')
            ->with(
                'success',
                'Profile information updated successfully.'
            );
    }

    /**
     * Change the authenticated user's password.
     */
    public function updatePassword(
        UpdatePasswordRequest $request,
        ProfileService $profileService
    ): RedirectResponse {
        $validated = $request->validated();

        $profileService->updatePassword(
            $request->user(),
            $validated['current_password'],
            $validated['password']
        );

        $request->user()->refresh();

        $request->session()->regenerate(true);

        $request->session()->forget(
            'auth.password_confirmed_at'
        );

        Cookie::queue(
            Cookie::forget(
                Auth::guard('web')->getRecallerName()
            )
        );

        return redirect()
            ->route('profile.edit')
            ->with(
                'password_success',
                'Your password has been updated successfully.'
            );
    }
}
